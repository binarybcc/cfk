<?php

declare(strict_types=1);

namespace CFK\Avatar;

/**
 * Avatar Manager - Silhouetted Avatar System
 *
 * Creates dignified, privacy-preserving avatars for children based on
 * age and gender. Uses PNG images or generated SVG silhouettes.
 *
 * @package CFK\Avatar
 */
class Manager
{
    /** @var array<string, string> Available avatar categories */
    public const CATEGORIES = [
        'infant' => 'Infant (0-2)',
        'male_toddler' => 'Male Toddler (3-5)',
        'female_toddler' => 'Female Toddler (3-5)',
        'male_child' => 'Male Child (6-10)',
        'female_child' => 'Female Child (6-10)',
        'male_teen' => 'Male Teen (11-18)',
        'female_teen' => 'Female Teen (11-18)'
    ];

    /**
     * Get avatar for a child based on age and gender
     *
     * @param array<string, mixed> $child Child data with age and gender
     * @return string Avatar image path URL
     */
    public static function getAvatarForChild(array $child): string
    {
        $category = self::determineAvatarCategory((int) $child['age_months'], (string) $child['gender']);
        return self::getAvatarImagePath($category);
    }

    /**
     * Determine avatar category based on age and gender
     *
     * @param int $age Child age
     * @param string $gender Child gender (M/F)
     * @return string Avatar category identifier
     */
    private static function determineAvatarCategory(int $age, string $gender): string
    {
        // Infant/Toddler (0-4)
        if ($age <= 4) {
            return $gender === 'M' ? 'male_toddler' : 'female_toddler';
        }

        // Elementary (5-10)
        if ($age <= 10) {
            return $gender === 'M' ? 'male_elementary' : 'female_elementary';
        }

        // Middle School (11-13)
        if ($age <= 13) {
            return $gender === 'M' ? 'male_middle' : 'female_middle';
        }

        // High School (14+)
        return $gender === 'M' ? 'male_highschool' : 'female_highschool';
    }

    /**
     * Get avatar image path from PNG files
     *
     * @param string $category Avatar category
     * @return string Full URL to avatar image
     */
    private static function getAvatarImagePath(string $category): string
    {
        // Map categories to image files
        $imageMap = [
            'male_toddler' => 'b-4boysm.png',           // Boys 0-4
            'female_toddler' => 'b-4girlsm.png',        // Girls 0-4
            'male_elementary' => 'elementaryboysm.png', // Boys 5-10
            'female_elementary' => 'elementarygirlsm.png', // Girls 5-10
            'male_middle' => 'middleboysm.png',         // Boys 11-13
            'female_middle' => 'middlegirlsm.png',      // Girls 11-13
            'male_highschool' => 'hsboysm.png',         // Boys 14+
            'female_highschool' => 'hsgirlsm.png'       // Girls 14+
        ];

        // Get the image filename
        $filename = $imageMap[$category] ?? 'b-4girlsm.png';

        // Return full URL using baseUrl helper
        return baseUrl('assets/images/' . $filename);
    }

    /**
     * Generate silhouetted avatar SVG
     *
     * @param string $category Avatar category
     * @return string Data URI with base64 encoded SVG
     */
    private static function generateSilhouettedAvatar(string $category): string
    {
        $svgData = self::getSvgData($category);
        return 'data:image/svg+xml;base64,' . base64_encode($svgData);
    }

    /**
     * Get SVG data for each avatar category
     *
     * @param string $category Avatar category
     * @return string SVG markup
     */
    private static function getSvgData(string $category): string
    {
        $baseColor = '#2c5530'; // Christmas green silhouette
        $bgColor = '#f8f9fa';   // Light background

        return match ($category) {
            'infant' => self::getInfantSvg($baseColor, $bgColor),
            'male_toddler' => self::getMaleToddlerSvg($baseColor, $bgColor),
            'female_toddler' => self::getFemaleToddlerSvg($baseColor, $bgColor),
            'male_child' => self::getMaleChildSvg($baseColor, $bgColor),
            'female_child' => self::getFemaleChildSvg($baseColor, $bgColor),
            'male_teen' => self::getMaleTeenSvg($baseColor, $bgColor),
            'female_teen' => self::getFemaleTeenSvg($baseColor, $bgColor),
            default => self::getDefaultSvg($baseColor, $bgColor),
        };
    }

    /**
     * Infant avatar (0-2 years) - gender neutral
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getInfantSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <!-- Baby silhouette -->
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Large head -->
        <circle cx="0" cy="-40" r="35"/>
        <!-- Small body -->
        <rect x="-20" y="-10" width="40" height="35" rx="8"/>
        <!-- Short arms -->
        <ellipse cx="-25" cy="0" rx="8" ry="20"/>
        <ellipse cx="25" cy="0" rx="8" ry="20"/>
        <!-- Short legs -->
        <ellipse cx="-12" cy="35" rx="10" ry="25"/>
        <ellipse cx="12" cy="35" rx="10" ry="25"/>
    </g>
    <!-- Decorative elements -->
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">👶 Infant</text>
</svg>
SVG;
    }

    /**
     * Male toddler avatar (3-5 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getMaleToddlerSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <circle cx="0" cy="-50" r="32"/>
        <!-- Short hair -->
        <path d="M -25,-75 Q 0,-85 25,-75 Q 20,-65 15,-70 Q 0,-75 -15,-70 Q -20,-65 -25,-75 Z"/>
        <!-- Body -->
        <rect x="-18" y="-20" width="36" height="40" rx="6"/>
        <!-- Arms -->
        <ellipse cx="-22" cy="-5" rx="7" ry="22"/>
        <ellipse cx="22" cy="-5" rx="7" ry="22"/>
        <!-- Legs -->
        <ellipse cx="-10" cy="35" rx="8" ry="28"/>
        <ellipse cx="10" cy="35" rx="8" ry="28"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">🧒 Male Toddler</text>
</svg>
SVG;
    }

    /**
     * Female toddler avatar (3-5 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getFemaleToddlerSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <circle cx="0" cy="-50" r="32"/>
        <!-- Longer hair with pigtails -->
        <circle cx="-30" cy="-60" r="8"/>
        <circle cx="30" cy="-60" r="8"/>
        <path d="M -25,-75 Q 0,-85 25,-75 Q 20,-65 15,-70 Q 0,-75 -15,-70 Q -20,-65 -25,-75 Z"/>
        <!-- Body -->
        <rect x="-18" y="-20" width="36" height="40" rx="6"/>
        <!-- Arms -->
        <ellipse cx="-22" cy="-5" rx="7" ry="22"/>
        <ellipse cx="22" cy="-5" rx="7" ry="22"/>
        <!-- Legs -->
        <ellipse cx="-10" cy="35" rx="8" ry="28"/>
        <ellipse cx="10" cy="35" rx="8" ry="28"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">👧 Female Toddler</text>
</svg>
SVG;
    }

    /**
     * Male child avatar (6-10 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getMaleChildSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <circle cx="0" cy="-60" r="28"/>
        <!-- Short hair -->
        <path d="M -22,-80 Q 0,-90 22,-80 Q 18,-70 10,-75 Q 0,-80 -10,-75 Q -18,-70 -22,-80 Z"/>
        <!-- Taller body -->
        <rect x="-16" y="-35" width="32" height="50" rx="6"/>
        <!-- Arms -->
        <ellipse cx="-20" cy="-15" rx="6" ry="25"/>
        <ellipse cx="20" cy="-15" rx="6" ry="25"/>
        <!-- Longer legs -->
        <ellipse cx="-8" cy="35" rx="7" ry="35"/>
        <ellipse cx="8" cy="35" rx="7" ry="35"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">👦 Male Child</text>
</svg>
SVG;
    }

    /**
     * Female child avatar (6-10 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getFemaleChildSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <circle cx="0" cy="-60" r="28"/>
        <!-- Longer hair -->
        <path d="M -25,-80 Q 0,-90 25,-80 Q 25,-65 20,-70 Q 0,-85 -20,-70 Q -25,-65 -25,-80 Z"/>
        <ellipse cx="0" cy="-70" rx="30" ry="15"/>
        <!-- Body -->
        <rect x="-16" y="-35" width="32" height="50" rx="6"/>
        <!-- Arms -->
        <ellipse cx="-20" cy="-15" rx="6" ry="25"/>
        <ellipse cx="20" cy="-15" rx="6" ry="25"/>
        <!-- Legs -->
        <ellipse cx="-8" cy="35" rx="7" ry="35"/>
        <ellipse cx="8" cy="35" rx="7" ry="35"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">👧 Female Child</text>
</svg>
SVG;
    }

    /**
     * Male teen avatar (11-18 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getMaleTeenSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <ellipse cx="0" cy="-70" rx="26" ry="30"/>
        <!-- Short hair -->
        <path d="M -20,-90 Q 0,-100 20,-90 Q 15,-80 8,-85 Q 0,-90 -8,-85 Q -15,-80 -20,-90 Z"/>
        <!-- Taller, broader body -->
        <rect x="-18" y="-45" width="36" height="60" rx="8"/>
        <!-- Longer arms -->
        <ellipse cx="-22" cy="-20" rx="7" ry="30"/>
        <ellipse cx="22" cy="-20" rx="7" ry="30"/>
        <!-- Long legs -->
        <ellipse cx="-9" cy="35" rx="8" ry="40"/>
        <ellipse cx="9" cy="35" rx="8" ry="40"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">🧑 Male Teen</text>
</svg>
SVG;
    }

    /**
     * Female teen avatar (11-18 years)
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getFemaleTeenSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <!-- Head -->
        <ellipse cx="0" cy="-70" rx="26" ry="30"/>
        <!-- Longer hair -->
        <path d="M -28,-85 Q 0,-95 28,-85 Q 25,-70 20,-75 Q 0,-90 -20,-75 Q -25,-70 -28,-85 Z"/>
        <ellipse cx="0" cy="-80" rx="32" ry="18"/>
        <!-- Body -->
        <rect x="-16" y="-45" width="32" height="60" rx="8"/>
        <!-- Arms -->
        <ellipse cx="-20" cy="-20" rx="6" ry="30"/>
        <ellipse cx="20" cy="-20" rx="6" ry="30"/>
        <!-- Legs -->
        <ellipse cx="-8" cy="35" rx="7" ry="40"/>
        <ellipse cx="8" cy="35" rx="7" ry="40"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">👩 Female Teen</text>
</svg>
SVG;
    }

    /**
     * Default/fallback avatar
     *
     * @param string $color Silhouette color
     * @param string $bg Background color
     * @return string SVG markup
     */
    private static function getDefaultSvg(string $color, string $bg): string
    {
        return <<<SVG
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="{$bg}" rx="8"/>
    <g fill="{$color}" transform="translate(150,150)">
        <circle cx="0" cy="-40" r="30"/>
        <rect x="-20" y="-15" width="40" height="50" rx="8"/>
        <ellipse cx="-25" cy="-5" rx="8" ry="25"/>
        <ellipse cx="25" cy="-5" rx="8" ry="25"/>
        <ellipse cx="-10" cy="50" rx="10" ry="35"/>
        <ellipse cx="10" cy="50" rx="10" ry="35"/>
    </g>
    <text x="150" y="280" text-anchor="middle" font-family="Arial, sans-serif"
          font-size="12" fill="#666">🎄 Child Profile</text>
</svg>
SVG;
    }

    /**
     * Get all available avatar categories
     *
     * @return array<string, string> Category identifier => description mapping
     */
    public static function getAvailableCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Test function to generate all avatar types
     *
     * @return array<string, string> Category => SVG data URI mapping
     */
    public static function generateTestAvatars(): array
    {
        $avatars = [];
        foreach (array_keys(self::CATEGORIES) as $category) {
            $avatars[$category] = self::generateSilhouettedAvatar($category);
        }
        return $avatars;
    }
}
