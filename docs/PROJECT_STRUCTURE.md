# 🏗️ Project Structure

## Current Architecture (Phase 1 + 2 Complete)

```
AI-HACKATHON/
│
├── index.html                 # Main entry point
├── README.md                  # Project documentation
├── MIGRATION.md              # Migration guide (this refactor)
│
├── assets/                    # Static assets
│   ├── images/
│   │   └── logo_cropped.png
│   └── icons/
│
├── css/                       # Modular CSS (NEW ✨)
│   ├── main.css              # Main import file
│   ├── README.md             # CSS documentation
│   │
│   ├── base/                 # Foundation styles
│   │   ├── variables.css     # Design tokens (colors, spacing, etc.)
│   │   ├── reset.css         # CSS reset & base elements
│   │   └── typography.css    # Typography system
│   │
│   ├── components/           # Reusable UI components
│   │   ├── buttons.css       # Button variants & states
│   │   ├── cards.css         # Card layouts (feature, pricing, testimonial)
│   │   ├── forms.css         # Form inputs & validation
│   │   └── badges.css        # Badge styles
│   │
│   └── layout/               # Page layout components
│       ├── header.css        # Dynamic pill header
│       ├── footer.css        # Footer with columns
│       └── grid.css          # Grid system utilities
│
├── js/                        # JavaScript (Future Phase 4)
│   ├── main.js
│   ├── components/
│   └── utils/
│
└── pages/                     # Additional pages & legacy
    ├── index.html            # Legacy backup
    ├── styles.css            # Section styles (Phase 3 TODO)
    ├── script.js             # Main JavaScript
    │
    └── auth/                 # Authentication pages
        ├── signin.html
        ├── register.html
        └── forgot-password.html
```

## 📦 File Sizes (Estimated)

| File | Lines | Purpose |
|------|-------|---------|
| `css/main.css` | 60 | Import manager |
| `css/base/variables.css` | 85 | Design system |
| `css/base/reset.css` | 80 | CSS reset |
| `css/base/typography.css` | 60 | Text styles |
| `css/components/buttons.css` | 65 | Buttons |
| `css/components/cards.css` | 170 | Cards |
| `css/components/forms.css` | 95 | Forms |
| `css/components/badges.css` | 60 | Badges |
| `css/layout/header.css` | 150 | Header |
| `css/layout/footer.css` | 90 | Footer |
| `css/layout/grid.css` | 55 | Grids |
| `pages/styles.css` | ~2000 | Sections (Phase 3) |

## 🎨 Design System

### Colors
- **Primary**: `#5cc0eb` (Cyan blue)
- **Accent**: `#B392F0` (Purple pastel)
- **Text**: `#24292F`, `#57606A`, `#8B949E`
- **Background**: `#FFFFFF`, `#F6F8FA`

### Spacing (8pt Grid)
- XS: 8px, SM: 16px, MD: 24px, LG: 32px
- XL: 48px, 2XL: 64px, 3XL: 80px, 4XL: 120px

### Typography
- Font: Apple system fonts
- Scale: 1.6rem base, clamp() for responsive

### Shadows
- Layered box-shadows with primary color tint
- Elevation: xs → sm → md → lg → xl

## 🚀 Component Library

### Buttons
- `.btn` - Primary button
- `.btn-outline` - Outlined variant
- `.btn-large` / `.btn-small` - Size variants

### Cards
- `.feature-card` - Feature showcase
- `.pricing-card` - Pricing plans
- `.testimonial-card` - User reviews
- `.tech-card` - Tech stack (3D effects)

### Forms
- Text inputs with focus states
- File upload custom styling
- Checkbox/radio with accent color

### Layout
- `.header` - Fixed dynamic pill header
- `.footer` - Multi-column footer
- `.grid-2/3/4` - Grid layouts

## 📊 Maintenance Notes

### When adding new components:
1. Create file in `css/components/`
2. Import in `css/main.css`
3. Follow naming convention (BEM-like)
4. Use CSS variables for colors/spacing

### When editing existing styles:
1. Find the appropriate modular file
2. Edit in place
3. No need to search through 2000+ lines

### Performance:
- Modular CSS allows tree-shaking (future)
- Can selectively load components
- Easier to identify unused styles

## 🎯 Future Phases

### Phase 3: Extract Sections
- Move sections from `pages/styles.css` to `css/sections/`
- Create `hero.css`, `features.css`, `tech-stack.css`, etc.

### Phase 4: Modular JavaScript
- Split `pages/script.js` into modules
- Create `js/components/` for reusable logic
- ES6 modules for better organization
