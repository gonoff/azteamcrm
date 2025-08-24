# AZTEAM CRM Documentation

This folder contains all documentation for the AZTEAM CRM/ERP system.

## 📁 Document Index

### System Documentation
- **[CLAUDE.md](../CLAUDE.md)** - Claude Code guidance (kept in root for tool access)
- **[azteam_system_design.md](azteam_system_design.md)** - Original system design specifications
- **[personal_workspace_view.md](personal_workspace_view.md)** - Personal workspace feature design

### Issue Tracking & Fixes
- **[CRITICAL_FIXES_TODO.md](CRITICAL_FIXES_TODO.md)** - 🚨 Priority fixes checklist
- **[codebase_issues_report.md](codebase_issues_report.md)** - Deep analysis of codebase problems

### Implementation Plans
- **[customer_fixes_implementation_plan.md](customer_fixes_implementation_plan.md)** - Customer module fixes
- **[orders_module_fixes_implementation_plan.md](orders_module_fixes_implementation_plan.md)** - Orders module overhaul

## Quick Links

### 🔴 Critical Issues
See [CRITICAL_FIXES_TODO.md](CRITICAL_FIXES_TODO.md) for system-breaking issues that need immediate attention.

### 📋 Implementation Status
- Customer Module: ✅ Fixes documented, partially implemented
- Orders Module: 📝 Plan created, implementation pending
- Database Schema: ⚠️ Critical updates needed

### 🛠️ Development Workflow
1. Check [CRITICAL_FIXES_TODO.md](CRITICAL_FIXES_TODO.md) for priority fixes
2. Review implementation plans before making changes
3. Update fix status in tracking documents
4. Test thoroughly using checklists provided

## Document Organization

```
/docs/
├── README.md                              # This file
├── CRITICAL_FIXES_TODO.md                 # Priority fixes tracker
├── codebase_issues_report.md              # Deep code analysis
├── customer_fixes_implementation_plan.md  # Customer module fixes
├── orders_module_fixes_implementation_plan.md # Orders module overhaul
├── azteam_system_design.md               # Original design specs
└── personal_workspace_view.md            # Future feature design

/CLAUDE.md (kept in root for Claude Code access)
```

## Priority Actions

1. **Run critical SQL fixes** - See CRITICAL_FIXES_TODO.md items #1-3
2. **Delete orphaned code** - Remove LineItem files
3. **Fix UI consistency** - Standardize button colors
4. **Test payment flow** - After database updates

---

*Last Updated: August 24, 2025*