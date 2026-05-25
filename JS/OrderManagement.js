const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const rows = document.querySelectorAll('#ordersTable tbody tr');

const modal = document.getElementById('orderModal');
const modalOrderNumber = document.getElementById('modalOrderNumber');
const modalBody = document.getElementById('modalBody');

/* SEARCH + FILTER */

function filterOrders(){

    const search = searchInput.value.toLowerCase();
    const status = statusFilter.value;

    rows.forEach(row => {

        const text = row.innerText.toLowerCase();
        const rowStatus = row.dataset.status;

        const matchSearch = text.includes(search);

        const matchStatus =
            status === 'all' ||
            rowStatus === status;

        row.style.display =
            matchSearch && matchStatus
            ? ''
            : 'none';
    });
}

searchInput.addEventListener('keyup', filterOrders);

statusFilter.addEventListener('change', filterOrders);

/* MODAL */

rows.forEach((row, index) => {

    row.addEventListener('click', () => {

        const order = orders[index];

        modal.style.display = 'flex';

        modalOrderNumber.innerText =
            `Order Details: ${order.order_number}`;

        let itemsHTML = '';

        order.items.forEach(item => {

            itemsHTML += `
                <div class="modal-item">
                    <span>
                        ${item.product_name}
                        ${item.size ? `(${item.size})` : ''}
                        x${item.quantity}
                    </span>

                    <span>
                        ₱${item.subtotal.toLocaleString()}
                    </span>
                </div>
            `;
        });

        modalBody.innerHTML = `
            <p><strong>Customer:</strong> ${order.customer_name}</p>

            <p><strong>Email:</strong> ${order.customer_email}</p>

            <p><strong>Amount:</strong>
                ₱${order.total_amount.toLocaleString()}
            </p>

            <p><strong>Payment:</strong>
                ${order.payment_method}
                (${order.payment_status})
            </p>

            <br>

            <h4>Items</h4>

            ${itemsHTML}

            ${
                order.notes
                ? `<div class="notes">${order.notes}</div>`
                : ''
            }
        `;
    });
});

function closeModal(){

    modal.style.display = 'none';
}

/* Close outside modal */

window.onclick = function(event){

    if(event.target === modal){

        closeModal();
    }
}

/* Status update */

document.querySelectorAll('.status-select')
.forEach(select => {

    select.addEventListener('change', function(){

        alert(`Order status updated to ${this.value}`);

        // Add AJAX / fetch here for database update
    });
});