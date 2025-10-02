<?php

declare(strict_types=1);

namespace CFK\Services;

use CFK\Models\Child;
use CFK\Models\Sponsorship;
use CFK\Interfaces\ChildRepositoryInterface;
use CFK\Interfaces\SponsorshipRepositoryInterface;
use CFK\Interfaces\SponsorshipServiceInterface;
use DateTime;
use Exception;

/**
 * Service for sponsorship business logic
 */
class SponsorshipService implements SponsorshipServiceInterface
{
    private const SELECTION_TIMEOUT_HOURS = 2;

    public function __construct(
        private ChildRepositoryInterface $childRepository,
        private SponsorshipRepositoryInterface $sponsorshipRepository
    ) {}

    /**
     * Select a child for sponsorship
     */
    public function selectChild(
        int $childId, 
        array $sponsorData, 
        string $type = 'individual',
        string $sessionId = null
    ): array {
        $child = $this->childRepository->findById($childId);
        
        if (!$child) {
            return ['success' => false, 'message' => 'Child not found'];
        }

        if (!$child->isAvailable()) {
            return ['success' => false, 'message' => 'Child is no longer available'];
        }

        try {
            // Calculate expiration time
            $expiresAt = (new DateTime())->modify('+' . self::SELECTION_TIMEOUT_HOURS . ' hours');
            
            // Create sponsorship record
            $sponsorshipData = [
                'child_id' => $childId,
                'sponsor_name' => $sponsorData['name'],
                'sponsor_email' => $sponsorData['email'],
                'sponsor_phone' => $sponsorData['phone'] ?? '',
                'type' => $type,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'session_id' => $sessionId,
                'notes' => $sponsorData['notes'] ?? null
            ];
            
            $sponsorshipId = $this->sponsorshipRepository->create($sponsorshipData);
            
            // Update child status
            $this->childRepository->updateStatus($childId, 'selected');
            
            // Handle family/sibling sponsorship
            if (in_array($type, ['sibling', 'family'])) {
                $this->handleFamilySponsorshipSelection($child, $type);
            }
            
            return [
                'success' => true,
                'message' => 'Child selected successfully',
                'sponsorship_id' => $sponsorshipId,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to select child: ' . $e->getMessage()];
        }
    }

    /**
     * Confirm a sponsorship
     */
    public function confirmSponsorship(int $sponsorshipId, array $additionalData = []): array
    {
        $sponsorship = $this->sponsorshipRepository->findById($sponsorshipId);
        
        if (!$sponsorship) {
            return ['success' => false, 'message' => 'Sponsorship not found'];
        }

        if (!$sponsorship->isPending()) {
            return ['success' => false, 'message' => 'Sponsorship is not pending confirmation'];
        }

        if ($sponsorship->isExpired()) {
            return ['success' => false, 'message' => 'Sponsorship selection has expired'];
        }

        try {
            // Confirm the sponsorship
            $this->sponsorshipRepository->confirm($sponsorshipId);
            
            // Update child status
            $this->childRepository->updateStatus($sponsorship->childId, 'sponsored');
            
            // Update sponsorship details if provided
            if (!empty($additionalData['notes'])) {
                $this->childRepository->updateSponsorshipDetails(
                    $sponsorship->childId,
                    $additionalData['notes']
                );
            }
            
            // Handle family/sibling sponsorship confirmation
            if (in_array($sponsorship->type, ['sibling', 'family'])) {
                $this->handleFamilySponsorshipConfirmation($sponsorship);
            }
            
            return [
                'success' => true,
                'message' => 'Sponsorship confirmed successfully'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to confirm sponsorship: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel a sponsorship
     */
    public function cancelSponsorship(int $sponsorshipId, string $reason = null): array
    {
        $sponsorship = $this->sponsorshipRepository->findById($sponsorshipId);
        
        if (!$sponsorship) {
            return ['success' => false, 'message' => 'Sponsorship not found'];
        }

        if ($sponsorship->isCancelled()) {
            return ['success' => false, 'message' => 'Sponsorship is already cancelled'];
        }

        try {
            // Cancel the sponsorship
            $this->sponsorshipRepository->cancel($sponsorshipId, $reason);
            
            // Update child status back to available
            $this->childRepository->updateStatus($sponsorship->childId, 'available');
            
            return [
                'success' => true,
                'message' => 'Sponsorship cancelled successfully'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to cancel sponsorship: ' . $e->getMessage()];
        }
    }

    /**
     * Get available sponsorship options for a child
     */
    public function getAvailableOptions(int $childId): array
    {
        $child = $this->childRepository->findById($childId);
        
        if (!$child) {
            return [];
        }

        $options = ['individual'];
        $siblings = $this->childRepository->getSiblings($childId);
        
        // Check for available siblings
        $availableSiblings = array_filter($siblings, fn($sibling) => $sibling->isAvailable());
        
        if (!empty($availableSiblings)) {
            $options[] = 'sibling';
        }
        
        // Check if entire family is available
        $familyStats = $this->childRepository->getFamilyStats($child->getBaseFamilyId());
        if ($familyStats['available_children'] >= 2) {
            $options[] = 'family';
        }
        
        return $options;
    }

    /**
     * Clean up expired selections
     */
    public function cleanupExpiredSelections(): int
    {
        return $this->sponsorshipRepository->cleanupExpired();
    }

    /**
     * Get sponsorship statistics
     */
    public function getStatistics(int $days = 30): array
    {
        return $this->sponsorshipRepository->getStats($days);
    }

    /**
     * Handle family/sibling sponsorship selection
     */
    private function handleFamilySponsorshipSelection(Child $child, string $type): void
    {
        $siblings = $this->childRepository->getSiblings($child->id);
        $availableSiblings = array_filter($siblings, fn($sibling) => $sibling->isAvailable());
        
        if ($type === 'sibling') {
            // Mark available siblings as selected
            foreach ($availableSiblings as $sibling) {
                $this->childRepository->updateStatus($sibling->id, 'selected');
            }
        } elseif ($type === 'family') {
            // Mark all family members as selected
            $familyMembers = $this->childRepository->findByFamilyId($child->getBaseFamilyId() . '%');
            foreach ($familyMembers as $member) {
                if ($member->isAvailable() && $member->id !== $child->id) {
                    $this->childRepository->updateStatus($member->id, 'selected');
                }
            }
        }
    }

    /**
     * Handle family/sibling sponsorship confirmation
     */
    private function handleFamilySponsorshipConfirmation(Sponsorship $sponsorship): void
    {
        $child = $this->childRepository->findById($sponsorship->childId);
        
        if ($sponsorship->type === 'sibling') {
            $siblings = $this->childRepository->getSiblings($child->id);
            foreach ($siblings as $sibling) {
                if ($sibling->isSelected()) {
                    $this->childRepository->updateStatus($sibling->id, 'sponsored');
                }
            }
        } elseif ($sponsorship->type === 'family') {
            $familyMembers = $this->childRepository->findByFamilyId($child->getBaseFamilyId() . '%');
            foreach ($familyMembers as $member) {
                if ($member->isSelected() && $member->id !== $child->id) {
                    $this->childRepository->updateStatus($member->id, 'sponsored');
                }
            }
        }
    }
}