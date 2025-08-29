/**
 * AZTEAM CRM - Collapsible Sidebar Enhancement
 * Modern sidebar with toggle functionality, state persistence, and responsive behavior
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        STORAGE_KEY: 'azteam-sidebar-state',
        ANIMATION_DURATION: 300,
        MOBILE_BREAKPOINT: 768
    };
    
    // DOM Elements
    let sidebar = null;
    let toggleButton = null;
    let mobileMenuButton = null;
    let mobileBackdrop = null;
    let mobileHeader = null;
    let mainContent = null;
    let tooltips = [];
    
    // State management
    let isCollapsed = false;
    let isMobile = false;
    
    /**
     * Initialize the sidebar functionality
     */
    function init() {
        // Check if we're on a page with sidebar
        sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        
        toggleButton = document.getElementById('sidebarToggle');
        mobileMenuButton = document.getElementById('mobileMenuButton');
        mobileBackdrop = document.getElementById('mobileBackdrop');
        mobileHeader = document.getElementById('mobileHeader');
        mainContent = document.getElementById('mainContent');
        
        if (!toggleButton && !mobileMenuButton) {
            console.warn('No toggle buttons found');
            return;
        }
        
        // Set up initial state
        setupInitialState();
        setupEventListeners();
        setupTooltips();
        handleResize(); // Check initial mobile state
        
        console.log('AZTEAM Sidebar initialized successfully');
    }
    
    /**
     * Setup initial sidebar state from localStorage
     */
    function setupInitialState() {
        // Load saved state from localStorage
        const savedState = localStorage.getItem(CONFIG.STORAGE_KEY);
        isCollapsed = savedState === 'collapsed';
        
        // Apply initial state
        applySidebarState(false); // No animation on page load
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Desktop toggle button click
        if (toggleButton) {
            toggleButton.addEventListener('click', handleToggle);
        }
        
        // Mobile menu button click
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', handleMobileToggle);
        }
        
        // Mobile backdrop click
        if (mobileBackdrop) {
            mobileBackdrop.addEventListener('click', handleBackdropClick);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboard);
        
        // Window resize
        window.addEventListener('resize', debounce(handleResize, 100));
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', handleOutsideClick);
    }
    
    /**
     * Handle sidebar toggle (desktop)
     */
    function handleToggle(event) {
        event.preventDefault();
        event.stopPropagation();
        
        if (isMobile) {
            // On mobile, delegate to mobile handler
            handleMobileToggle(event);
            return;
        }
        
        // Desktop toggle behavior
        isCollapsed = !isCollapsed;
        applySidebarState(true);
        saveState();
        
        // Update tooltips visibility
        updateTooltips();
    }
    
    /**
     * Handle mobile menu toggle
     */
    function handleMobileToggle(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const isOpen = sidebar.classList.contains('show');
        
        if (isOpen) {
            // Close mobile sidebar
            sidebar.classList.remove('show');
            if (mobileBackdrop) mobileBackdrop.classList.remove('show');
            if (mobileHeader) mobileHeader.classList.remove('sidebar-open');
        } else {
            // Open mobile sidebar
            sidebar.classList.add('show');
            if (mobileBackdrop) mobileBackdrop.classList.add('show');
            if (mobileHeader) mobileHeader.classList.add('sidebar-open');
        }
    }
    
    /**
     * Handle mobile backdrop click
     */
    function handleBackdropClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        // Close sidebar when clicking backdrop
        if (sidebar.classList.contains('show')) {
            handleMobileToggle();
        }
    }
    
    /**
     * Handle keyboard shortcuts
     */
    function handleKeyboard(event) {
        // ESC to collapse sidebar (desktop only)
        if (event.key === 'Escape' && !isMobile && !isCollapsed) {
            isCollapsed = true;
            applySidebarState(true);
            saveState();
            updateTooltips();
        }
        
        // Ctrl/Cmd + B to toggle sidebar
        if ((event.ctrlKey || event.metaKey) && event.key === 'b') {
            event.preventDefault();
            handleToggle(event);
        }
    }
    
    /**
     * Handle window resize
     */
    function handleResize() {
        const wasMobile = isMobile;
        isMobile = window.innerWidth <= CONFIG.MOBILE_BREAKPOINT;
        
        // If switching from/to mobile, update behavior
        if (wasMobile !== isMobile) {
            if (isMobile) {
                // Switching to mobile
                sidebar.classList.remove('collapsed', 'expanded');
                if (mobileBackdrop) mobileBackdrop.classList.remove('show');
                if (mobileHeader) mobileHeader.classList.remove('sidebar-open');
                updateMainContentMargin(0);
            } else {
                // Switching to desktop
                sidebar.classList.remove('show');
                if (mobileBackdrop) mobileBackdrop.classList.remove('show');
                if (mobileHeader) mobileHeader.classList.remove('sidebar-open');
                applySidebarState(false);
            }
        }
        
        updateTooltips();
    }
    
    /**
     * Handle clicks outside sidebar on mobile
     */
    function handleOutsideClick(event) {
        if (!isMobile) return;
        
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isToggleButton = event.target.closest('#sidebarToggle');
        const isMobileMenuButton = event.target.closest('#mobileMenuButton');
        const isMobileHeader = mobileHeader && mobileHeader.contains(event.target);
        const isBackdrop = event.target.closest('#mobileBackdrop');
        
        // Don't close if clicking on sidebar, toggle buttons, mobile header, or backdrop is handled separately
        if (!isClickInsideSidebar && !isToggleButton && !isMobileMenuButton && !isMobileHeader && !isBackdrop && sidebar.classList.contains('show')) {
            handleMobileToggle();
        }
    }
    
    /**
     * Apply sidebar state (collapsed/expanded)
     */
    function applySidebarState(animate = true) {
        if (isMobile) return; // Don't apply desktop states on mobile
        
        // Add/remove CSS classes
        if (isCollapsed) {
            sidebar.classList.remove('expanded');
            sidebar.classList.add('collapsed');
            updateMainContentMargin(60); // Collapsed width
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('expanded');
            updateMainContentMargin(220); // Expanded width
        }
        
        // Add animation class temporarily if needed
        if (animate) {
            sidebar.style.transition = `all ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.4, 0, 0.2, 1)`;
            setTimeout(() => {
                sidebar.style.transition = '';
            }, CONFIG.ANIMATION_DURATION);
        }
    }
    
    /**
     * Update main content margin and width based on sidebar width
     */
    function updateMainContentMargin(sidebarWidth) {
        if (!mainContent || isMobile) return;
        
        // Update both margin and width to prevent viewport overflow
        mainContent.style.marginLeft = sidebarWidth + 'px';
        mainContent.style.width = `calc(100vw - ${sidebarWidth}px)`;
        mainContent.style.transition = `margin-left ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.4, 0, 0.2, 1), width ${CONFIG.ANIMATION_DURATION}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        
        // Clear transition after animation
        setTimeout(() => {
            mainContent.style.transition = '';
        }, CONFIG.ANIMATION_DURATION);
    }
    
    /**
     * Setup tooltips for collapsed state
     */
    function setupTooltips() {
        const navLinks = sidebar.querySelectorAll('.nav-link[data-bs-toggle="tooltip"]');
        
        navLinks.forEach(link => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltip = new bootstrap.Tooltip(link, {
                    placement: 'right',
                    trigger: 'hover',
                    delay: { show: 500, hide: 100 }
                });
                tooltips.push(tooltip);
            }
        });
    }
    
    /**
     * Update tooltip visibility based on sidebar state
     */
    function updateTooltips() {
        tooltips.forEach(tooltip => {
            if (isCollapsed && !isMobile) {
                tooltip.enable();
            } else {
                tooltip.disable();
            }
        });
    }
    
    /**
     * Save current state to localStorage
     */
    function saveState() {
        if (isMobile) return; // Don't save mobile state
        
        localStorage.setItem(CONFIG.STORAGE_KEY, isCollapsed ? 'collapsed' : 'expanded');
    }
    
    /**
     * Debounce function for performance
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Public API
     */
    window.AZTeamSidebar = {
        toggle: handleToggle,
        mobileToggle: handleMobileToggle,
        collapse: () => {
            if (!isMobile && !isCollapsed) {
                isCollapsed = true;
                applySidebarState(true);
                saveState();
                updateTooltips();
            }
        },
        expand: () => {
            if (!isMobile && isCollapsed) {
                isCollapsed = false;
                applySidebarState(true);
                saveState();
                updateTooltips();
            }
        },
        closeMobile: () => {
            if (isMobile && sidebar.classList.contains('show')) {
                handleMobileToggle();
            }
        },
        isCollapsed: () => isCollapsed,
        isMobile: () => isMobile,
        isMobileOpen: () => isMobile && sidebar.classList.contains('show')
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();