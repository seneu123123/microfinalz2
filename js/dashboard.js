   // Theme Toggle
const themeToggle = document.getElementById("themeToggle")
const body = document.body

// Load theme from localStorage
const savedTheme = localStorage.getItem("theme")
if (savedTheme === "dark") {
  body.classList.add("dark-mode")
}

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    body.classList.toggle("dark-mode")
    const isDark = body.classList.contains("dark-mode")
    localStorage.setItem("theme", isDark ? "dark" : "light")
  })
}

// Sidebar Toggle
const sidebarToggle = document.getElementById("sidebarToggle")
const sidebar = document.getElementById("sidebar")

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed")
    localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"))
  })
}

// Load sidebar state from localStorage
const sidebarCollapsed = localStorage.getItem("sidebarCollapsed")
if (sidebarCollapsed === "true" && sidebar) {
  sidebar.classList.add("collapsed")
}

// Mobile Menu Toggle
const mobileMenuBtn = document.getElementById("mobileMenuBtn")

if (mobileMenuBtn && sidebar) {
  mobileMenuBtn.addEventListener("click", () => {
    sidebar.classList.toggle("mobile-open")
  })
}

// Close sidebar when clicking outside on mobile
document.addEventListener("click", (e) => {
  if (window.innerWidth <= 768 && sidebar && mobileMenuBtn) {
    if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
      sidebar.classList.remove("mobile-open")
    }
  }
})

// Submenu Toggle
function initializeSubmenus() {
  const navItems = document.querySelectorAll(".nav-item.has-submenu")

  navItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault()
      e.stopPropagation()

      const module = item.getAttribute("data-module")
      const submenu = document.getElementById(`submenu-${module}`)

      if (submenu) {
        // Close other submenus
        document.querySelectorAll(".submenu").forEach((sub) => {
          if (sub !== submenu) {
            sub.classList.remove("active")
            const otherItem = sub.previousElementSibling
            if (otherItem && otherItem.classList.contains("has-submenu")) {
              otherItem.classList.remove("active")
            }
          }
        })

        // Toggle current submenu
        submenu.classList.toggle("active")
        item.classList.toggle("active")
      }
    })
  })
}

// Prevent submenu links from toggling parent
function initializeSubmenuLinks() {
  document.querySelectorAll(".submenu-item").forEach((item) => {
    item.addEventListener("click", (e) => {
      e.stopPropagation()
    })
  })
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  initializeSubmenus()
  initializeSubmenuLinks()

  // Initialize Lucide icons
  const lucide = window.lucide 
  if (typeof lucide !== "undefined") {
    lucide.createIcons()
  }
})

// Re-initialize when new content is loaded (for dynamic pages)
window.initializeNavigation = function() {
  setTimeout(() => {
    initializeSubmenus()
    initializeSubmenuLinks()
    const lucide = window.lucide 
    if (typeof lucide !== "undefined") {
      lucide.createIcons()
    }
  }, 100)
}