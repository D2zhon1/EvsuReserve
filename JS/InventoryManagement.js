const modal = document.getElementById('stockModal');
const stockQty = document.getElementById('stockQty');
const modalTitle = document.getElementById('modalTitle');

let currentProductId = null;

function openModal(id, name, qty){

    currentProductId = id;

    modal.style.display = 'flex';

    modalTitle.innerText = `Adjust Stock: ${name}`;

    stockQty.value = qty;
}

function closeModal(){

    modal.style.display = 'none';
}

function updateStock(){

    const qty = stockQty.value;

    alert(`Product ID ${currentProductId} updated to quantity ${qty}`);

    closeModal();

    // Here you can use AJAX / fetch to update database
}

/* Search Function */

document.getElementById('searchInput').addEventListener('keyup', function(){

    const value = this.value.toLowerCase();

    const rows = document.querySelectorAll('#inventoryTable tbody tr');

    rows.forEach(row => {

        const text = row.innerText.toLowerCase();

        row.style.display = text.includes(value)
            ? ''
            : 'none';
    });
});

/* Close modal outside click */

window.onclick = function(event){

    if(event.target === modal){

        closeModal();
    }
}