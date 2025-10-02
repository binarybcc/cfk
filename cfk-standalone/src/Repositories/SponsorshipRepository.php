<?php

declare(strict_types=1);

namespace CFK\Repositories;

use CFK\Config\Database;
use CFK\Models\Sponsorship;
use CFK\Interfaces\SponsorshipRepositoryInterface;
use PDO;
use DateTime;

/**
 * Repository for sponsorship data access operations
 */
class SponsorshipRepository implements SponsorshipRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find sponsorship by ID
     */
    public function findById(int $id): ?Sponsorship
    {
        $stmt = $this->db->prepare('SELECT * FROM sponsorships WHERE id = ?');
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        return $data ? Sponsorship::fromArray($data) : null;
    }

    /**
     * Find sponsorship by child ID
     */
    public function findByChildId(int $childId): ?Sponsorship
    {
        $stmt = $this->db->prepare('
            SELECT * FROM sponsorships 
            WHERE child_id = ? AND status != "cancelled"
            ORDER BY selected_at DESC 
            LIMIT 1
        ');
        $stmt->execute([$childId]);
        
        $data = $stmt->fetch();
        return $data ? Sponsorship::fromArray($data) : null;
    }

    /**
     * Find sponsorship by session ID
     */
    public function findBySessionId(string $sessionId): ?Sponsorship
    {
        $stmt = $this->db->prepare('
            SELECT * FROM sponsorships 
            WHERE session_id = ? AND status = "selected"
            ORDER BY selected_at DESC 
            LIMIT 1
        ');
        $stmt->execute([$sessionId]);
        
        $data = $stmt->fetch();
        return $data ? Sponsorship::fromArray($data) : null;
    }

    /**
     * Create new sponsorship
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO sponsorships 
            (child_id, sponsor_name, sponsor_email, sponsor_phone, type, status, selected_at, expires_at, session_id, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['child_id'],
            $data['sponsor_name'],
            $data['sponsor_email'],
            $data['sponsor_phone'],
            $data['type'],
            'selected',
            (new DateTime())->format('Y-m-d H:i:s'),
            $data['expires_at'] ?? null,
            $data['session_id'] ?? null,
            $data['notes'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Confirm sponsorship
     */
    public function confirm(int $sponsorshipId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE sponsorships 
            SET status = "confirmed", confirmed_at = NOW()
            WHERE id = ? AND status = "selected"
        ');
        
        return $stmt->execute([$sponsorshipId]);
    }

    /**
     * Cancel sponsorship
     */
    public function cancel(int $sponsorshipId, string $reason = null): bool
    {
        $stmt = $this->db->prepare('
            UPDATE sponsorships 
            SET status = "cancelled", cancelled_at = NOW(), notes = CONCAT(COALESCE(notes, ""), ?, ?)
            WHERE id = ?
        ');
        
        $cancelNote = $reason ? "\nCancellation reason: " . $reason : '';
        return $stmt->execute(["\nCancelled at: " . date('Y-m-d H:i:s'), $cancelNote, $sponsorshipId]);
    }

    /**
     * Find expired sponsorships
     */
    public function findExpired(): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM sponsorships 
            WHERE status = "selected" 
            AND expires_at IS NOT NULL 
            AND expires_at < NOW()
        ');
        $stmt->execute();
        
        return array_map([Sponsorship::class, 'fromArray'], $stmt->fetchAll());
    }

    /**
     * Get sponsorship statistics
     */
    public function getStats(int $days = 30): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                COUNT(*) as total_sponsorships,
                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed_sponsorships,
                SUM(CASE WHEN status = "selected" THEN 1 ELSE 0 END) as pending_sponsorships,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_sponsorships,
                SUM(CASE WHEN type = "individual" THEN 1 ELSE 0 END) as individual_sponsorships,
                SUM(CASE WHEN type = "sibling" THEN 1 ELSE 0 END) as sibling_sponsorships,
                SUM(CASE WHEN type = "family" THEN 1 ELSE 0 END) as family_sponsorships
            FROM sponsorships 
            WHERE selected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ');
        
        $stmt->execute([$days]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Get recent sponsorship activity
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, c.name as child_name, c.family_id
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            ORDER BY s.selected_at DESC
            LIMIT ?
        ');
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Update sponsorship notes
     */
    public function updateNotes(int $sponsorshipId, string $notes): bool
    {
        $stmt = $this->db->prepare('UPDATE sponsorships SET notes = ? WHERE id = ?');
        return $stmt->execute([$notes, $sponsorshipId]);
    }

    /**
     * Cleanup expired selections
     */
    public function cleanupExpired(): int
    {
        Database::beginTransaction();
        
        try {
            // Get expired sponsorships
            $expiredSponsorships = $this->findExpired();
            
            // Cancel expired sponsorships
            $stmt = $this->db->prepare('
                UPDATE sponsorships 
                SET status = "cancelled", cancelled_at = NOW(), 
                    notes = CONCAT(COALESCE(notes, ""), "\nAuto-cancelled due to expiration")
                WHERE status = "selected" AND expires_at < NOW()
            ');
            $stmt->execute();
            
            // Update children status back to available
            $childIds = array_map(fn($s) => $s->childId, $expiredSponsorships);
            if (!empty($childIds)) {
                $placeholders = str_repeat('?,', count($childIds) - 1) . '?';
                $stmt = $this->db->prepare("
                    UPDATE children 
                    SET status = 'available' 
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute($childIds);
            }
            
            Database::commit();
            return count($expiredSponsorships);
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}