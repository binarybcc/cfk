<?php
declare(strict_types=1);

// Security constant
define('CFK_APP', true);

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/avatar_manager.php';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar System Test - CFK</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f5f5f5; }
        .avatars-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; }
        .avatar-item { background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .avatar-item img { max-width: 200px; height: auto; margin-bottom: 1rem; }
        .avatar-item h3 { color: #2c5530; margin-bottom: 0.5rem; }
        .test-children { margin-top: 3rem; }
        h1, h2 { color: #2c5530; text-align: center; }
    </style>
</head>
<body>
    <h1>🎭 Christmas for Kids - Avatar System Test</h1>
    <p style="text-align: center; color: #666; font-style: italic;">
        Privacy-preserving silhouetted avatars for dignified child representation
    </p>

    <h2>All Avatar Categories</h2>
    <div class="avatars-grid">
        <?php
        $categories = CFK_Avatar_Manager::getAvailableCategories();
        $testAvatars = CFK_Avatar_Manager::generateTestAvatars();
        
        foreach ($categories as $key => $label): ?>
            <div class="avatar-item">
                <img src="<?php echo $testAvatars[$key]; ?>" alt="<?php echo $label; ?>">
                <h3><?php echo $label; ?></h3>
                <p><strong>Category:</strong> <?php echo $key; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="test-children">
        <h2>Sample Children with Auto-Assigned Avatars</h2>
        <div class="avatars-grid">
            <?php
            // Test children with different ages and genders
            $testChildren = [
                ['name' => 'Baby Emma', 'age' => 1, 'gender' => 'F'],
                ['name' => 'Toddler Jake', 'age' => 3, 'gender' => 'M'],
                ['name' => 'Toddler Mia', 'age' => 4, 'gender' => 'F'],
                ['name' => 'Child Marcus', 'age' => 7, 'gender' => 'M'],
                ['name' => 'Child Sofia', 'age' => 9, 'gender' => 'F'],
                ['name' => 'Teen Alex', 'age' => 13, 'gender' => 'M'],
                ['name' => 'Teen Isabella', 'age' => 16, 'gender' => 'F'],
            ];
            
            foreach ($testChildren as $child): ?>
                <div class="avatar-item">
                    <img src="<?php echo getPhotoUrl(null, $child); ?>" alt="Avatar for <?php echo $child['name']; ?>">
                    <h3><?php echo $child['name']; ?></h3>
                    <p><strong>Age:</strong> <?php echo $child['age']; ?> years</p>
                    <p><strong>Gender:</strong> <?php echo $child['gender'] === 'M' ? 'Male' : 'Female'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="margin-top: 3rem; text-align: center; color: #666;">
        <p><a href="index.php" style="color: #2c5530;">← Back to Main Site</a></p>
        <p><small>Avatar system ensures complete privacy while providing visual representation</small></p>
    </div>
</body>
</html>