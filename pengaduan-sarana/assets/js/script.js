// Sanitisasi HTML untuk mencegah XSS
function escapeHtml(text) {
  if (!text) return "";
  return text.toString().replace(
    /[&<>"']/g,
    (m) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      })[m],
  );
}

let isFirstLoad = true;

// Fungsi Fetch & Render Histori
async function loadHistori(nis, showLoading = false) {
  const historiTableBody = document.getElementById("historiTableBody");
  if (!historiTableBody) return;

  if (!nis) {
    historiTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Masukkan NIS di atas untuk menampilkan data.</td></tr>`;
    return;
  }

  // Tampilkan teks loading hanya saat pertama kali dibuka atau pencarian manual
  if (showLoading && isFirstLoad) {
    historiTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Memuat data histori...</td></tr>`;
  }

  try {
    const response = await fetch(
      `index.php?action=get_histori&nis=${encodeURIComponent(nis)}`,
    );
    const result = await response.json();

    if (result.status === "success") {
      isFirstLoad = false;

      if (result.data.length === 0) {
        historiTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada histori pengaduan untuk NIS ini.</td></tr>`;
        return;
      }

      const newHtml = result.data
        .map((item) => {
          let badgeClass = "badge-menunggu";
          if (item.status === "Proses") badgeClass = "badge-proses";
          if (item.status === "Selesai") badgeClass = "badge-selesai";

          return `
              <tr>
                  <td><small>${item.tanggal}</small></td>
                  <td>
                      <strong>${escapeHtml(item.ket_kategori)}</strong><br>
                      <small class="text-muted">${escapeHtml(item.lokasi)}</small>
                  </td>
                  <td>${escapeHtml(item.ket)}</td>
                  <td><span class="badge badge-status ${badgeClass}">${item.status}</span></td>
                  <td><small>${item.feedback ? escapeHtml(item.feedback) : "<em>Belum ada feedback</em>"}</small></td>
              </tr>
          `;
        })
        .join("");

      // Hanya ganti HTML jika ada perubahan data agar tidak flicker
      if (historiTableBody.innerHTML !== newHtml) {
        historiTableBody.innerHTML = newHtml;
      }
    }
  } catch (error) {
    console.error("Error fetching histori:", error);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const formAspirasi = document.getElementById("formAspirasi");
  const alertContainer = document.getElementById("alertContainer");
  const searchNisInput = document.getElementById("searchNis");
  const btnCariHistori = document.getElementById("btnCariHistori");
  const nisInput = document.getElementById("nis");

  function showAlert(type, message) {
    if (!alertContainer) return;
    alertContainer.innerHTML = `
      <div class="alert alert-${type === "success" ? "success" : "danger"} alert-dismissible fade show mb-3" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `;
  }

  const initialNis = searchNisInput ? searchNisInput.value.trim() : "";
  if (initialNis) {
    loadHistori(initialNis, true);
  }

  // Submit Form Aspirasi via AJAX (Instan)
  if (formAspirasi) {
    formAspirasi.addEventListener("submit", async (e) => {
      e.preventDefault();

      const btnSubmit = document.getElementById("btnSubmit");
      if (btnSubmit) btnSubmit.disabled = true;

      const formData = new FormData(formAspirasi);

      try {
        const response = await fetch("index.php?action=simpan", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.status === "success") {
          showAlert("success", result.message);

          const currentNis = nisInput ? nisInput.value : "";
          formAspirasi.reset();
          if (nisInput) nisInput.value = currentNis;

          // Langsung update tabel secara instan tanpa reload
          if (searchNisInput) {
            searchNisInput.value = currentNis;
            loadHistori(currentNis, false);
          }
        } else {
          showAlert("error", result.message);
        }
      } catch (error) {
        showAlert("error", "Terjadi kesalahan sistem!");
      } finally {
        if (btnSubmit) btnSubmit.disabled = false;
      }
    });
  }

  // Cari Histori via Tombol / Keypress
  if (btnCariHistori && searchNisInput) {
    btnCariHistori.addEventListener("click", () => {
      isFirstLoad = true;
      loadHistori(searchNisInput.value.trim(), true);
    });

    searchNisInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        isFirstLoad = true;
        loadHistori(searchNisInput.value.trim(), true);
      }
    });
  }

  // Auto Polling setiap 3 detik (Live update jika Admin mengubah status)
  setInterval(() => {
    const activeNis = searchNisInput ? searchNisInput.value.trim() : "";
    if (activeNis) {
      loadHistori(activeNis, false);
    }
  }, 3000);
});
