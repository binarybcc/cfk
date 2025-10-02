# CFK Sample CSV - Critical Issues Analysis

## 🚨 **MAJOR STRUCTURAL PROBLEMS**

### 1. **Completely Wrong Column Structure**
```csv
Current: Name,Short description,Purchase note,Categories
Needed:  name,age,gender,family_id,family_name,grade,shirt_size,pant_size,shoe_size,jacket_size,interests,wishes,special_needs
```
**Impact**: Cannot import directly - needs complete restructuring

### 2. **Data Mashed Into Single "Name" Field**
```csv
Example: "001A: Female 2."
Should be:
- family_id: 001, child_letter: A  
- gender: F
- age: 2
- name: [MISSING - no actual names!]
```

### 3. **No Actual Child Names** 
- Only has IDs like "001A: Female 2"
- **CRITICAL**: Need real first names for dignified representation
- Cannot display "001A" to sponsors - looks like inventory numbers

### 4. **All Data Crammed Into Description Fields**
```csv
"Pants: Toddler 3T. Shirt: Toddler 3T. Shoes: Toddler 7. Greatest Need: Diapers..."
```
**Should be separate fields:**
- shirt_size: "3T"
- pant_size: "3T" 
- shoe_size: "7"
- special_needs: "Diapers (Pampers) Size 5"
- wishes: "Cocmelon Toys, Baby Shark Toys..."

### 5. **Family Grouping Unclear**
- IDs like 005A, 005B, 005C, 005D, 005E, 005F, 005G suggest 7 siblings (!!)
- No family names provided
- Age inconsistencies within families

## 🔧 **DATA PARSING CHALLENGES**

### Size Format Issues
```csv
Current Chaos:
- "Pants: Toddler 3T"
- "Shirt: aMens Small" (typo "aMens")  
- "Shoes: Youth 3"
- "Pants: Mens 29x30"
- "Shoes: Toddler 12"
```

### Mixed Content in Descriptions
- Clothing sizes mixed with wish lists
- "Greatest Need" mixed with toys
- No clear separation between categories

### Age Category Mismatch
```csv
"005G: Male 4" categorized as "Birth to 4 Years" ✓
But "005F: Female 6" categorized as "Elementary" ✓
Shows some logic but data is buried in wrong fields
```

## 🛠 **REQUIRED CONVERSION STRATEGY**

### Phase 1: Data Extraction Parser
```php
function parseCfkSampleRow($row) {
    // Extract from "Name" field: "001A: Female 2."
    preg_match('/(\d+)([A-Z]): (Male|Female) (\d+)/', $row['Name'], $matches);
    
    return [
        'family_id' => $matches[1],
        'child_letter' => $matches[2], 
        'gender' => $matches[3] === 'Male' ? 'M' : 'F',
        'age' => (int)$matches[4],
        'name' => generateName($matches[3], $matches[4]), // Need to generate!
    ];
}
```

### Phase 2: Size Extraction
```php
function extractSizes($description) {
    $sizes = [];
    
    // Extract pants
    if (preg_match('/Pants: ([^.]+)\./', $description, $match)) {
        $sizes['pant_size'] = trim($match[1]);
    }
    
    // Extract shirt  
    if (preg_match('/Shirt: ([^.]+)\./', $description, $match)) {
        $sizes['shirt_size'] = trim($match[1]);
    }
    
    // Extract shoes
    if (preg_match('/Shoes: ([^.]+)\./', $description, $match)) {
        $sizes['shoe_size'] = trim($match[1]);
    }
    
    return $sizes;
}
```

### Phase 3: Wishes & Needs Separation  
```php
function extractWishesAndNeeds($description) {
    // Split on "Greatest Need:" and "Wish List:"
    $parts = preg_split('/(Greatest Need:|Wish List:)/', $description);
    
    return [
        'special_needs' => extractNeeds($parts[1] ?? ''),
        'wishes' => extractWishes($parts[2] ?? ''),
        'interests' => extractInterests($parts[2] ?? '')
    ];
}
```

## 🎯 **PROPOSED SOLUTION**

### Option 1: Smart Parser + Name Generation
```php
class CfkLegacyCsvParser {
    private $nameGenerator;
    
    public function convertLegacyCsv($filePath) {
        foreach ($rows as $row) {
            $child = $this->parseRow($row);
            $child['name'] = $this->generateAppropiateName($child);
            $this->createChild($child);
        }
    }
}
```

### Option 2: Two-Step Process
1. **Parse to intermediate format** - extract all data 
2. **Admin review screen** - let admin assign real names before import

### Option 3: Template Mapping Tool
- Create web interface to map CSV columns to our schema
- Show preview before import
- Allow data cleanup during mapping

## 📋 **CLEANED SAMPLE DATA PREVIEW**

Based on first few rows, here's what cleaned data should look like:

```csv
name,age,gender,family_id,family_name,shirt_size,pant_size,shoe_size,special_needs,wishes
"Emma",2,"F","001","Johnson Family","3T","3T","7","Diapers Size 5, Baby Wipes","Cocomelon Toys, Baby Shark Toys"  
"Marcus",16,"M","002","Smith Family","Small","Small","9","Coat XL","Gaming Headphones, Nintendo Switch"
"Sophia",8,"F","003","Davis Family","8","8","3","Pants, Shirts, Underwear","Soccer Ball, Bike, Lego Sets"
"Isabella",7,"F","003","Davis Family","7","7","1","Socks, Underwear","Bike, Craft Sets, Horse Books"
```

## ❓ **QUESTIONS FOR CLIENT**

1. **Names**: Should we generate realistic first names or ask you to provide them?

2. **Large Families**: Family 005 has 7 children (ages 4-13) - is this accurate?

3. **Size Validation**: Some sizes seem inconsistent (Girls 8 with Toddler 12 shoes) - clean up?

4. **Categories**: Keep the Birth-4/Elementary/Middle/High groupings or use age-based logic?

5. **Special Needs**: Separate "clothing needs" from "medical needs"?

## 🔨 **IMPLEMENTATION PRIORITY**

**HIGH PRIORITY**: Build the CSV parser immediately - this data format will likely be common for imports and we need a robust conversion system.

The current CSV is unusable without significant processing, but contains all the data we need - just in the wrong format.