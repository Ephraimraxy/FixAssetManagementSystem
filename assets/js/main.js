// main.js - Handles form submissions and fetches asset data via AJAX

document.addEventListener('DOMContentLoaded', function () {
    // Example: Handle asset form submission
    const assetForm = document.getElementById('assetForm');
    if (assetForm) {
        assetForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(assetForm);
            const response = await fetch(assetForm.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Asset saved successfully!');
            } else {
                alert('Error: ' + (result.message || 'Unknown error'));
            }
        });
    }

    // Example: Fetch asset data and display in a table
    const assetTable = document.getElementById('assetTable');
    if (assetTable) {
        fetch('/user/fetch_assets.php')
            .then(response => response.json())
            .then(data => {
                assetTable.innerHTML = '';
                data.assets.forEach(asset => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${asset.asset_id}</td>
                        <td>${asset.asset_name}</td>
                        <td>${asset.asset_type}</td>
                        <td>${asset.status}</td>
                    `;
                    assetTable.appendChild(row);
                });
            })
            .catch(err => {
                assetTable.innerHTML = '<tr><td colspan="4">Failed to load assets</td></tr>';
            });
    }
});
