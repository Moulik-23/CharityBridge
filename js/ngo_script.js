document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("ngo-register-form").addEventListener("submit", (e) => {
    e.preventDefault();

    const darpanId = document.querySelector("input[name='darpan_id']").value.trim();
    const cert12a = document.querySelector("input[name='certificate_12a']").files.length;
    const cert80g = document.querySelector("input[name='certificate_80g']").files.length;

    if (!darpanId) {
      alert("Darpan ID is required!");
      return;
    }

    if (!cert12a && !cert80g) {
      alert("Please upload at least one certificate (12A or 80G).");
      return;
    }

    alert("NGO Registration submitted successfully!");
  });
});
