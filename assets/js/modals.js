/**
 * Modal Utilities for Kansei Rental
 * Handles delete confirmation and status change modals
 */

// Delete Modal Handler
const DeleteModal = {
  modal: null,
  confirmMessage: null,
  confirmAction: null,
  modalTitle: null,
  modalIcon: null,

  init() {
    this.modal = document.getElementById("confirmModal");
    this.confirmMessage = document.getElementById("confirmMessage");
    this.confirmAction = document.getElementById("confirmAction");
    this.modalTitle = document.getElementById("confirmModalTitle");
    this.modalIcon = document.getElementById("confirmModalIcon");

    if (!this.modal) return;

    // Bind delete buttons
    this.bindDeleteButtons();
  },

  bindDeleteButtons() {
    document.querySelectorAll("[data-confirm]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        this.show({
          message:
            btn.getAttribute("data-confirm") ||
            "Apakah Anda yakin ingin menghapus data ini?",
          href: btn.getAttribute("href"),
          title: btn.getAttribute("data-title") || "Hapus Data?",
          icon: btn.getAttribute("data-icon") || "bi-trash3",
        });
      });
    });
  },

  show(options) {
    if (!this.modal) return;

    if (this.confirmMessage) {
      this.confirmMessage.textContent = options.message;
    }
    if (this.confirmAction) {
      this.confirmAction.setAttribute("href", options.href);
    }
    if (this.modalTitle) {
      this.modalTitle.textContent = options.title;
    }
    if (this.modalIcon) {
      this.modalIcon.className = "bi " + options.icon;
    }

    const bsModal = new bootstrap.Modal(this.modal);
    bsModal.show();
  },
};

// Status Change Modal Handler
const StatusModal = {
  modal: null,
  message: null,
  confirmAction: null,
  title: null,
  statusBadge: null,
  newStatusBadge: null,

  init() {
    this.modal = document.getElementById("statusModal");
    this.message = document.getElementById("statusMessage");
    this.confirmAction = document.getElementById("statusConfirmAction");
    this.title = document.getElementById("statusModalTitle");
    this.statusBadge = document.getElementById("currentStatusBadge");
    this.newStatusBadge = document.getElementById("newStatusBadge");

    if (!this.modal) return;

    // Bind status change buttons
    this.bindStatusButtons();
  },

  bindStatusButtons() {
    document.querySelectorAll("[data-status-change]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();

        const currentStatus = btn.getAttribute("data-current-status");
        const newStatus = btn.getAttribute("data-new-status");
        const transId = btn.getAttribute("data-trans-id");
        const carInfo = btn.getAttribute("data-car-info") || "";
        const memberName = btn.getAttribute("data-member-name") || "";

        this.show({
          currentStatus: currentStatus,
          newStatus: newStatus,
          href: btn.getAttribute("href"),
          carInfo: carInfo,
          memberName: memberName,
        });
      });
    });
  },

  getStatusLabel(status) {
    const labels = {
      booking: "Booking",
      approve: "Approved",
      ambil: "Sedang Disewa",
      kembali: "Selesai",
    };
    return labels[status] || status;
  },

  getStatusColor(status) {
    const colors = {
      booking: "#e0af68",
      approve: "#7dcfff",
      ambil: "#7aa2f7",
      kembali: "#9ece6a",
    };
    return colors[status] || "#565f89";
  },

  show(options) {
    if (!this.modal) return;

    const currentLabel = this.getStatusLabel(options.currentStatus);
    const newLabel = this.getStatusLabel(options.newStatus);

    if (this.title) {
      this.title.textContent = "Ubah Status Transaksi";
    }

    if (this.message) {
      let msg = `Ubah status dari <strong>${currentLabel}</strong> menjadi <strong>${newLabel}</strong>?`;
      if (options.carInfo) {
        msg += `<br><small class="text-muted mt-2 d-block">Mobil: ${options.carInfo}</small>`;
      }
      if (options.memberName) {
        msg += `<small class="text-muted">Member: ${options.memberName}</small>`;
      }
      this.message.innerHTML = msg;
    }

    if (this.statusBadge) {
      this.statusBadge.textContent = currentLabel;
      this.statusBadge.style.background = this.getStatusColor(
        options.currentStatus,
      );
    }

    if (this.newStatusBadge) {
      this.newStatusBadge.textContent = newLabel;
      this.newStatusBadge.style.background = this.getStatusColor(
        options.newStatus,
      );
    }

    if (this.confirmAction) {
      this.confirmAction.setAttribute("href", options.href);
    }

    const bsModal = new bootstrap.Modal(this.modal);
    bsModal.show();
  },
};

// Initialize modals on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  DeleteModal.init();
  StatusModal.init();
});

// Re-initialize after dynamic content load
function reinitModals() {
  DeleteModal.bindDeleteButtons();
  StatusModal.bindStatusButtons();
}

