/**
 * Main JavaScript for Rental JDM
 * Contains common functionality used across the website
 */

// Initialize Bootstrap Tooltips
function initTooltips() {
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
      trigger: "hover",
    });
  });
}

// Toast Function
function showToast(type, message) {
  const toastEl = document.getElementById("liveToast");
  const toastIcon = document.getElementById("toastIcon");
  const toastTitle = document.getElementById("toastTitle");
  const toastMessage = document.getElementById("toastMessage");

  if (!toastEl) return;

  // Remove previous classes
  toastEl.classList.remove("bg-success", "bg-danger", "bg-warning", "bg-info");

  // Set based on type
  switch (type) {
    case "success":
      toastEl.classList.add("bg-success");
      toastIcon.className = "bi bi-check-circle-fill me-2";
      toastTitle.textContent = "Berhasil!";
      break;
    case "error":
      toastEl.classList.add("bg-danger");
      toastIcon.className = "bi bi-x-circle-fill me-2";
      toastTitle.textContent = "Error!";
      break;
    case "warning":
      toastEl.classList.add("bg-warning");
      toastIcon.className = "bi bi-exclamation-triangle-fill me-2";
      toastTitle.textContent = "Peringatan!";
      break;
    case "info":
      toastEl.classList.add("bg-info");
      toastIcon.className = "bi bi-info-circle-fill me-2";
      toastTitle.textContent = "Info";
      break;
  }

  toastMessage.textContent = message;

  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}

// Check URL for messages
function checkUrlMessages() {
  const urlParams = new URLSearchParams(window.location.search);
  const msg = urlParams.get("msg");

  if (msg) {
    let message = "";
    let type = "success";

    switch (msg) {
      case "added":
        message = "Data berhasil ditambahkan!";
        break;
      case "updated":
        message = "Data berhasil diupdate!";
        break;
      case "deleted":
        message = "Data berhasil dihapus!";
        break;
      case "error":
        message = "Terjadi kesalahan!";
        type = "error";
        break;
      case "error_fk":
        const detail = urlParams.get("detail");
        message =
          "Data tidak dapat dihapus karena masih digunakan di tabel " +
          (detail || "lain") +
          "!";
        type = "error";
        break;
      default:
        message = msg;
    }

    showToast(type, message);

    // Clean URL
    const newUrl =
      window.location.href.split("?")[0] + "?page=" + urlParams.get("page");
    if (urlParams.get("p")) {
      window.history.replaceState(
        {},
        document.title,
        newUrl + "&p=" + urlParams.get("p"),
      );
    } else {
      window.history.replaceState({}, document.title, newUrl);
    }
  }
}

// Toggle Navigation Menu (Mobile)
function toggleNavMenu() {
  const navMenu = document.getElementById("navMenu");
  if (navMenu) {
    navMenu.classList.toggle("show");
  }
}

// Navbar scroll effect
function initNavbarScroll() {
  const navbar = document.querySelector(".top-navbar");
  if (navbar) {
    window.addEventListener("scroll", function () {
      if (window.scrollY > 20) {
        navbar.classList.add("scrolled");
      } else {
        navbar.classList.remove("scrolled");
      }
    });
  }
}

// Scroll Animation Observer
function initScrollAnimations() {
  const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.1,
  };

  const animationObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("animate-in");
      }
    });
  }, observerOptions);

  // Elements to animate on scroll
  const animateElements = document.querySelectorAll(
    ".stat-card, .transaksi-card, .kembali-card, .bayar-card, " +
      ".content-card, .card, .mini-stat-card, .mobil-card, " +
      ".page-header, .alert, .cars-grid-container > div",
  );

  animateElements.forEach((el, index) => {
    el.classList.add("animate-on-scroll");
    el.style.transitionDelay = `${index * 0.05}s`;
    animationObserver.observe(el);
  });
}

// Close nav menu when clicking outside on mobile
function initNavMenuClose() {
  document.addEventListener("click", function (e) {
    const navMenu = document.getElementById("navMenu");
    const hamburger = document.querySelector(".hamburger");

    if (navMenu && hamburger && window.innerWidth <= 768) {
      if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
        navMenu.classList.remove("show");
      }
    }
  });

  // Close nav menu when clicking a link
  document.querySelectorAll(".nav-menu .nav-link").forEach(function (link) {
    link.addEventListener("click", function () {
      document.getElementById("navMenu").classList.remove("show");
    });
  });
}

// Update datetime
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  };
  const dateTimeElement = document.getElementById("currentDateTime");
  if (dateTimeElement) {
    dateTimeElement.textContent = now.toLocaleDateString("id-ID", options);
  }
}

// Theme Switcher Functions
function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "dark";
  document.documentElement.setAttribute("data-theme", savedTheme);
  updateThemeIcon(savedTheme);
}

function toggleTheme() {
  const currentTheme =
    document.documentElement.getAttribute("data-theme") || "dark";
  const newTheme = currentTheme === "dark" ? "light" : "dark";

  document.documentElement.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
  updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
  const themeIcon = document.getElementById("themeIcon");
  if (themeIcon) {
    if (theme === "light") {
      themeIcon.className = "bi bi-sun-fill";
    } else {
      themeIcon.className = "bi bi-moon-stars-fill";
    }
  }
}

// Initialize all on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  initTooltips();
  checkUrlMessages();
  initNavbarScroll();
  initScrollAnimations();
  initNavMenuClose();
  initTheme();
  updateDateTime();

  // Update datetime every minute
  setInterval(updateDateTime, 60000);
});
