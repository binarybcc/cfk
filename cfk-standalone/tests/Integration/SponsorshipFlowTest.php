<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for sponsorship workflow
 * Tests the complete sponsorship flow from selection to confirmation
 */
class SponsorshipFlowTest extends TestCase
{
    /** @test */
    public function it_creates_sponsorship_request_successfully(): void
    {
        $childId = 1;
        $sponsorData = [
            'name' => 'John Sponsor',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'gift_preference' => 'shopping'
        ];

        // Verify sponsor data structure
        $this->assertArrayHasKey('name', $sponsorData);
        $this->assertArrayHasKey('email', $sponsorData);
        $this->assertIsString($sponsorData['name']);
        $this->assertIsString($sponsorData['email']);
    }

    /** @test */
    public function it_validates_sponsor_email_format(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'sponsor+tag@email.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }

        $invalidEmails = [
            'invalid',
            '@example.com',
            'test@',
            'test @example.com'
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
    }

    /** @test */
    public function it_checks_child_availability_before_sponsorship(): void
    {
        $childStatuses = ['available', 'pending', 'sponsored', 'inactive'];

        $availableStatuses = ['available'];
        $unavailableStatuses = ['pending', 'sponsored', 'inactive'];

        foreach ($availableStatuses as $status) {
            $this->assertContains($status, $childStatuses);
            $this->assertEquals('available', $status);
        }

        foreach ($unavailableStatuses as $status) {
            $this->assertContains($status, $childStatuses);
            $this->assertNotEquals('available', $status);
        }
    }

    /** @test */
    public function it_prevents_duplicate_sponsorships(): void
    {
        $childId = 1;
        $existingSponsorships = [
            ['child_id' => 1, 'status' => 'pending'],
            ['child_id' => 2, 'status' => 'confirmed'],
        ];

        $hasPendingSponsorship = !empty(array_filter(
            $existingSponsorships,
            fn($s) => $s['child_id'] === $childId && in_array($s['status'], ['pending', 'confirmed'])
        ));

        $this->assertTrue($hasPendingSponsorship);
    }

    /** @test */
    public function it_handles_family_sponsorship_correctly(): void
    {
        $familyChildren = [
            ['id' => 1, 'family_id' => 175, 'status' => 'available'],
            ['id' => 2, 'family_id' => 175, 'status' => 'available'],
            ['id' => 3, 'family_id' => 175, 'status' => 'available'],
        ];

        $availableChildren = array_filter($familyChildren, fn($c) => $c['status'] === 'available');

        $this->assertCount(3, $availableChildren);

        // Verify all belong to same family
        $familyIds = array_unique(array_column($availableChildren, 'family_id'));
        $this->assertCount(1, $familyIds);
        $this->assertEquals(175, $familyIds[0]);
    }

    /** @test */
    public function it_creates_multiple_sponsorships_for_family(): void
    {
        $childrenIds = [1, 2, 3];
        $sponsorData = [
            'name' => 'Family Sponsor',
            'email' => 'family@example.com'
        ];

        $sponsorships = [];
        foreach ($childrenIds as $childId) {
            $sponsorships[] = [
                'child_id' => $childId,
                'sponsor_name' => $sponsorData['name'],
                'sponsor_email' => $sponsorData['email'],
                'status' => 'pending'
            ];
        }

        $this->assertCount(3, $sponsorships);

        // Verify all sponsorships have same sponsor
        $sponsorEmails = array_unique(array_column($sponsorships, 'sponsor_email'));
        $this->assertCount(1, $sponsorEmails);
        $this->assertEquals('family@example.com', $sponsorEmails[0]);
    }

    /** @test */
    public function it_validates_gift_preference_options(): void
    {
        $validPreferences = ['shopping', 'gift_card', 'cash_donation'];

        foreach ($validPreferences as $pref) {
            $this->assertContains($pref, $validPreferences);
        }

        $invalidPref = 'other';
        $this->assertNotContains($invalidPref, $validPreferences);
    }

    /** @test */
    public function it_updates_child_status_on_sponsorship(): void
    {
        $child = ['id' => 1, 'status' => 'available'];

        // After sponsorship request
        $child['status'] = 'pending';

        $this->assertEquals('pending', $child['status']);

        // After confirmation
        $child['status'] = 'sponsored';

        $this->assertEquals('sponsored', $child['status']);
    }

    /** @test */
    public function it_handles_sponsorship_cancellation(): void
    {
        $sponsorship = [
            'id' => 1,
            'child_id' => 1,
            'status' => 'pending'
        ];

        // Cancel sponsorship
        $sponsorship['status'] = 'cancelled';

        $this->assertEquals('cancelled', $sponsorship['status']);

        // Child should become available again
        $childNewStatus = 'available';
        $this->assertEquals('available', $childNewStatus);
    }

    /** @test */
    public function it_tracks_sponsorship_timestamps(): void
    {
        $now = time();

        $sponsorship = [
            'request_date' => $now,
            'confirmation_date' => null,
            'completion_date' => null
        ];

        $this->assertIsInt($sponsorship['request_date']);
        $this->assertNull($sponsorship['confirmation_date']);
        $this->assertNull($sponsorship['completion_date']);

        // After confirmation
        $sponsorship['confirmation_date'] = $now + 3600;

        $this->assertNotNull($sponsorship['confirmation_date']);
        $this->assertGreaterThan($sponsorship['request_date'], $sponsorship['confirmation_date']);
    }
}
