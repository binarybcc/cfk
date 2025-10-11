# Alpine.js Patterns & Best Practices for CFK

**Version:** v1.4
**Last Updated:** 2025-10-10
**Alpine.js Version:** 3.14.1

This document provides reusable patterns and best practices for Alpine.js implementation in the CFK project.

---

## 🎯 Core Concepts

### What is Alpine.js?

Alpine.js is a lightweight JavaScript framework (15KB) that adds interactivity directly in HTML using special attributes called "directives."

**Think of it as:** jQuery for the modern era, but reactive and declarative.

---

## 📚 Essential Directives

### `x-data` - Component State
Initializes a component with data and methods.

```html
<div x-data="{ count: 0, message: 'Hello' }">
    <!-- Component content here -->
</div>
```

### `x-text` - Display Text
Sets the text content of an element.

```html
<span x-text="count"></span>
<!-- Displays: 0 -->
```

### `x-show` - Conditional Display
Shows/hides elements (element stays in DOM).

```html
<div x-show="count > 0">
    Count is greater than zero!
</div>
```

### `x-if` - Conditional Rendering
Adds/removes elements from DOM entirely.

```html
<template x-if="isActive">
    <div>This is active!</div>
</template>
```

### `x-for` - Loops
Iterate over arrays to create repeated elements.

```html
<template x-for="item in items" :key="item.id">
    <div x-text="item.name"></div>
</template>
```

### `x-model` - Two-Way Binding
Syncs form input values with data.

```html
<input type="text" x-model="search">
<p>You searched for: <span x-text="search"></span></p>
```

### `@click` - Event Handling
Runs code when events occur.

```html
<button @click="count++">Increment</button>
<button @click="reset()">Reset</button>
```

### `x-transition` - Animations
Adds smooth transitions when elements appear/disappear.

```html
<div x-show="isVisible" x-transition>
    Fades in and out smoothly
</div>
```

### `:class` - Dynamic Classes
Conditionally apply CSS classes.

```html
<button :class="{ 'active': isActive, 'disabled': !enabled }">
    Click Me
</button>
```

### `:style` - Dynamic Styles
Conditionally apply inline styles.

```html
<div :style="'width: ' + progress + '%'">
    Progress Bar
</div>
```

---

## 🎨 Common Patterns for CFK

### Pattern 1: Live Dashboard Statistics

```html
<div x-data="{
    stats: {
        total: <?php echo $totalChildren; ?>,
        sponsored: <?php echo $sponsoredCount; ?>,
        available: <?php echo $availableCount; ?>
    },
    loading: false,
    lastUpdate: '<?php echo date('g:i A'); ?>',

    async refreshStats() {
        this.loading = true;
        try {
            const response = await fetch('api/stats.php');
            const data = await response.json();
            this.stats = data;
            this.lastUpdate = new Date().toLocaleTimeString();
        } catch (error) {
            console.error('Failed to refresh:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="setInterval(() => refreshStats(), 30000)">

    <!-- Stats Display -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number" x-text="stats.sponsored">0</div>
            <div class="stat-label">Sponsored</div>
        </div>

        <div class="stat-card">
            <div class="stat-number" x-text="stats.available">0</div>
            <div class="stat-label">Available</div>
        </div>

        <div class="stat-card">
            <div class="stat-number" x-text="Math.round((stats.sponsored / stats.total) * 100) + '%'">
                0%
            </div>
            <div class="stat-label">Completion Rate</div>
        </div>
    </div>

    <!-- Last Updated -->
    <p>Last updated: <span x-text="lastUpdate"></span></p>

    <!-- Manual Refresh Button -->
    <button @click="refreshStats()" :disabled="loading">
        <span x-show="!loading">🔄 Refresh</span>
        <span x-show="loading">⏳ Loading...</span>
    </button>
</div>
```

---

### Pattern 2: Instant Search & Filter

```html
<div x-data="{
    search: '',
    genderFilter: 'all',
    statusFilter: 'all',
    children: <?php echo json_encode($allChildren); ?>,

    get filteredChildren() {
        return this.children.filter(child => {
            // Search filter
            const matchesSearch = child.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                 child.id.toString().includes(this.search);

            // Gender filter
            const matchesGender = this.genderFilter === 'all' ||
                                 child.gender === this.genderFilter;

            // Status filter
            const matchesStatus = this.statusFilter === 'all' ||
                                 child.status === this.statusFilter;

            return matchesSearch && matchesGender && matchesStatus;
        });
    }
}">

    <!-- Search Input -->
    <input
        type="text"
        x-model="search"
        placeholder="Search by name or ID..."
        class="search-input">

    <!-- Filter Dropdowns -->
    <select x-model="genderFilter">
        <option value="all">All Genders</option>
        <option value="M">Male</option>
        <option value="F">Female</option>
    </select>

    <select x-model="statusFilter">
        <option value="all">All Statuses</option>
        <option value="available">Available</option>
        <option value="sponsored">Sponsored</option>
    </select>

    <!-- Results Count -->
    <p>
        Showing <strong x-text="filteredChildren.length"></strong>
        of <strong x-text="children.length"></strong> children
    </p>

    <!-- Results Grid -->
    <div class="children-grid">
        <template x-for="child in filteredChildren" :key="child.id">
            <div class="child-card" x-transition>
                <img :src="child.photo_url" :alt="child.name">
                <h3 x-text="child.name"></h3>
                <p>Age: <span x-text="child.age"></span></p>
                <span class="badge" :class="child.status === 'sponsored' ? 'badge-success' : 'badge-warning'"
                      x-text="child.status">
                </span>
            </div>
        </template>

        <!-- No Results -->
        <div x-show="filteredChildren.length === 0" x-transition>
            <p>No children match your filters.</p>
        </div>
    </div>
</div>
```

---

### Pattern 3: CSV Upload with Live Validation

```html
<div x-data="{
    file: null,
    fileName: '',
    fileSize: 0,
    errors: [],
    warnings: [],

    handleFileSelect(event) {
        this.file = event.target.files[0];
        this.fileName = this.file ? this.file.name : '';
        this.fileSize = this.file ? this.file.size : 0;
        this.validate();
    },

    validate() {
        this.errors = [];
        this.warnings = [];

        if (!this.file) {
            this.errors.push('Please select a file');
            return false;
        }

        // File extension check
        if (!this.fileName.toLowerCase().endsWith('.csv')) {
            this.errors.push('File must be a CSV (.csv extension)');
        }

        // File size check (5MB limit)
        if (this.fileSize > 5 * 1024 * 1024) {
            this.errors.push('File size exceeds 5MB limit');
        }

        // Warning for large files
        if (this.fileSize > 1 * 1024 * 1024) {
            this.warnings.push('Large file - import may take several minutes');
        }

        return this.errors.length === 0;
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
}">

    <form method="post" enctype="multipart/form-data"
          @submit="if (!validate()) { $event.preventDefault(); }">

        <!-- File Input -->
        <input
            type="file"
            name="csv_file"
            accept=".csv"
            @change="handleFileSelect($event)">

        <!-- File Info -->
        <div x-show="file" x-transition>
            <p><strong>File:</strong> <span x-text="fileName"></span></p>
            <p><strong>Size:</strong> <span x-text="formatFileSize(fileSize)"></span></p>
        </div>

        <!-- Errors -->
        <div x-show="errors.length > 0" class="alert alert-danger" x-transition>
            <strong>⚠️ Cannot Upload:</strong>
            <ul>
                <template x-for="error in errors" :key="error">
                    <li x-text="error"></li>
                </template>
            </ul>
        </div>

        <!-- Warnings -->
        <div x-show="warnings.length > 0 && errors.length === 0"
             class="alert alert-warning" x-transition>
            <strong>⚡ Notice:</strong>
            <ul>
                <template x-for="warning in warnings" :key="warning">
                    <li x-text="warning"></li>
                </template>
            </ul>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="errors.length > 0 || !file"
            :class="{ 'btn-disabled': errors.length > 0 || !file }">
            📤 Upload and Preview
        </button>
    </form>
</div>
```

---

### Pattern 4: Modal Dialog

```html
<div x-data="{ modalOpen: false }">
    <!-- Trigger Button -->
    <button @click="modalOpen = true">
        View Child Details
    </button>

    <!-- Modal Overlay -->
    <div x-show="modalOpen"
         x-transition.opacity
         @click="modalOpen = false"
         class="modal-overlay">

        <!-- Modal Content -->
        <div @click.stop class="modal-content" x-transition>
            <h2>Child Details</h2>
            <p>Detailed information here...</p>

            <!-- Close Button -->
            <button @click="modalOpen = false">Close</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
}
</style>
```

---

### Pattern 5: Accordion/FAQ

```html
<div x-data="{ activeSection: null }">
    <!-- Question 1 -->
    <div class="faq-item">
        <button
            @click="activeSection = activeSection === 1 ? null : 1"
            class="faq-question">
            <span>How do I sponsor a child?</span>
            <span x-text="activeSection === 1 ? '−' : '+'">+</span>
        </button>
        <div x-show="activeSection === 1"
             x-collapse
             class="faq-answer">
            <p>To sponsor a child, browse our available children...</p>
        </div>
    </div>

    <!-- Question 2 -->
    <div class="faq-item">
        <button
            @click="activeSection = activeSection === 2 ? null : 2"
            class="faq-question">
            <span>When are gifts due?</span>
            <span x-text="activeSection === 2 ? '−' : '+'">+</span>
        </button>
        <div x-show="activeSection === 2"
             x-collapse
             class="faq-answer">
            <p>Gifts are due by December 15th...</p>
        </div>
    </div>
</div>
```

---

### Pattern 6: Tabs

```html
<div x-data="{ activeTab: 'dashboard' }">
    <!-- Tab Buttons -->
    <div class="tabs">
        <button
            @click="activeTab = 'dashboard'"
            :class="{ 'active': activeTab === 'dashboard' }">
            Dashboard
        </button>
        <button
            @click="activeTab = 'children'"
            :class="{ 'active': activeTab === 'children' }">
            Children
        </button>
        <button
            @click="activeTab = 'reports'"
            :class="{ 'active': activeTab === 'reports' }">
            Reports
        </button>
    </div>

    <!-- Tab Content -->
    <div x-show="activeTab === 'dashboard'" x-transition>
        <h2>Dashboard Content</h2>
        <p>Statistics and graphs here...</p>
    </div>

    <div x-show="activeTab === 'children'" x-transition>
        <h2>Children Management</h2>
        <p>Child listing and management...</p>
    </div>

    <div x-show="activeTab === 'reports'" x-transition>
        <h2>Reports</h2>
        <p>Analytics and reports...</p>
    </div>
</div>
```

---

## 🎯 Best Practices

### 1. Keep Components Small
```html
<!-- ✅ Good: Focused component -->
<div x-data="{ count: 0 }">
    <button @click="count++">+</button>
    <span x-text="count"></span>
</div>

<!-- ❌ Bad: Too much in one component -->
<div x-data="{ /* 50 properties and 20 methods */ }">
    <!-- Hundreds of lines -->
</div>
```

### 2. Use Computed Properties (Getters)
```html
<div x-data="{
    items: [...],
    search: '',

    // ✅ Good: Computed property
    get filteredItems() {
        return this.items.filter(item =>
            item.name.includes(this.search)
        );
    }
}">
    <span x-text="filteredItems.length"></span>
</div>
```

### 3. Extract Complex Logic to Methods
```html
<div x-data="{
    value: 0,

    // ✅ Good: Complex logic in method
    calculate() {
        return this.value * 2 + 10;
    }
}">
    <span x-text="calculate()"></span>
</div>
```

### 4. Use x-init for Setup
```html
<div x-data="{ timer: null }"
     x-init="timer = setInterval(() => console.log('tick'), 1000)">
    <!-- Component setup code -->
</div>
```

### 5. Progressive Enhancement
```html
<!-- Always provide fallback for no-JS -->
<div x-data="{ search: '' }">
    <input x-model="search" placeholder="Search...">

    <!-- Enhanced experience -->
    <div x-show="search.length > 0">
        Instant results...
    </div>

    <!-- Fallback -->
    <noscript>
        <form method="GET">
            <input name="q">
            <button>Search</button>
        </form>
    </noscript>
</div>
```

---

## ⚠️ Common Mistakes to Avoid

### ❌ Don't Put Everything in One Component
```html
<!-- BAD -->
<div x-data="{ /* manages entire page */ }">
    <!-- Too much responsibility -->
</div>
```

### ❌ Don't Duplicate Data
```html
<!-- BAD -->
<div x-data="{ count: 0, countPlusOne: 1 }">

<!-- GOOD -->
<div x-data="{
    count: 0,
    get countPlusOne() { return this.count + 1; }
}">
```

### ❌ Don't Forget Progressive Enhancement
```html
<!-- BAD: Breaks without JS -->
<div x-data="..." x-show="true">
    Only works with JavaScript
</div>

<!-- GOOD: Works with or without JS -->
<div x-data="..." style="display: block;">
    Works always, enhanced with JS
</div>
```

---

## 🚀 Performance Tips

### 1. Use `x-show` for Frequent Toggles
```html
<!-- Fast: Element stays in DOM -->
<div x-show="isVisible">Content</div>
```

### 2. Use `x-if` for Expensive Content
```html
<!-- Better for heavy content that's rarely shown -->
<template x-if="shouldRender">
    <div><!-- Complex content --></div>
</template>
```

### 3. Debounce Search Input
```html
<input
    x-model="search"
    @input.debounce.300ms="performSearch()">
```

### 4. Lazy Load Large Lists
```html
<div x-data="{
    items: [],
    page: 1,
    loadMore() {
        // Fetch next page
    }
}">
    <!-- Display items -->
    <button @click="loadMore()">Load More</button>
</div>
```

---

## 📚 Useful Resources

- **Official Docs:** https://alpinejs.dev/
- **Examples:** https://alpinejs.dev/examples
- **Screencasts:** https://alpinecasts.com/
- **Discord:** https://discord.gg/alpinejs

---

**Last Updated:** 2025-10-10
**Maintained By:** CFK Development Team
