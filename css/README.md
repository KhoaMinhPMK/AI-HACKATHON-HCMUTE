# CSS Architecture Documentation

## 📂 Folder Structure

```
css/
├── main.css                 # Main entry point - imports all modules
├── base/                    # Base styles
│   ├── variables.css        # CSS custom properties (colors, spacing, etc.)
│   ├── reset.css           # CSS reset & base HTML styles
│   └── typography.css      # Font styles & text utilities
├── components/             # Reusable UI components
│   ├── buttons.css         # Button variants (.btn, .btn-outline, etc.)
│   ├── cards.css          # Card components (.feature-card, .pricing-card, etc.)
│   ├── forms.css          # Form inputs & controls
│   └── badges.css         # Badge components
└── layout/                 # Layout components
    ├── header.css         # Header navigation
    ├── footer.css         # Footer section
    └── grid.css           # Grid system utilities
```

## 🎨 Design System

### Colors
- Primary: `--main-color: #5cc0eb`
- Accent: `--accent-purple: #B392F0`
- Text: `--text-primary`, `--text-secondary`, `--text-tertiary`

### Spacing (8pt grid)
- xs: 8px, sm: 16px, md: 24px, lg: 32px, xl: 48px, 2xl: 64px, 3xl: 80px, 4xl: 120px

### Border Radius
- sm: 8px, md: 12px, lg: 16px, xl: 20px, 2xl: 24px, full: 9999px

### Transitions
- fast: 0.2s, base: 0.3s, smooth: 0.4s, bounce: 0.5s

## 🚀 Usage

Import `main.css` in your HTML:

```html
<link rel="stylesheet" href="css/main.css">
<link rel="stylesheet" href="pages/styles.css"> <!-- Legacy sections -->
```

## 📝 Next Steps (Phase 3)

Extract remaining sections from `pages/styles.css` into:
- `css/sections/hero.css`
- `css/sections/features.css`
- `css/sections/tech-stack.css`
- `css/sections/partners.css`
- `css/sections/pricing.css`
- `css/sections/testimonials.css`
- `css/sections/chatbot.css`

## 🎯 Benefits

- **Modular**: Each file has single responsibility
- **Maintainable**: Easy to find and update styles
- **Scalable**: Add new components without conflicts
- **Team-friendly**: Multiple developers can work simultaneously
- **Performance**: Only load what you need
