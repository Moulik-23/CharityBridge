function approveNgo(id, rowElement) {
    fetch("backend/approve_ngo.php?id=" + id)   // ✅ correct path
        .then(res => res.text())
        .then(data => {
            alert(data);
            if (rowElement) rowElement.remove();
        })
        .catch(err => console.error(err));
}

function rejectNgo(id, rowElement) {
    fetch("backend/reject_ngo.php?id=" + id)   // ✅ correct path
        .then(res => res.text())
        .then(data => {
            alert(data);
            if (rowElement) rowElement.remove();
        })
        .catch(err => console.error(err));
}
