const modal = document.getElementById('productModal');
const form = document.getElementById('productForm');
const sizesContainer = document.getElementById('sizesContainer');

let sizes = [];
let editingId = null;

/* OPEN FORM */

function openForm(){

    modal.style.display = 'flex';

    document.getElementById('modalTitle')
    .innerText = 'Add Product';

    form.reset();

    sizes = [];

    renderSizes();

    editingId = null;
}

/* CLOSE FORM */

function closeForm(){

    modal.style.display = 'none';
}

/* EDIT PRODUCT */

function editProduct(product){

    modal.style.display = 'flex';

    document.getElementById('modalTitle')
    .innerText = 'Edit Product';

    editingId = product.id;

    document.getElementById('name').value =
        product.name;

    document.getElementById('description').value =
        product.description;

    document.getElementById('category').value =
        product.category;

    document.getElementById('price').value =
        product.price;

    document.getElementById('markup_price').value =
        product.markup_price;

    document.getElementById('stock_quantity').value =
        product.stock_quantity;

    document.getElementById('image_url').value =
        product.image_url;

    document.getElementById('sku').value =
        product.sku;

    document.getElementById('is_active').checked =
        product.is_active;

    sizes = product.sizes_available || [];

    renderSizes();
}

/* ADD SIZE */

function addSize(){

    const input =
        document.getElementById('sizeInput');

    const value = input.value.trim();

    if(value && !sizes.includes(value)){

        sizes.push(value);

        renderSizes();

        input.value = '';
    }
}

/* RENDER SIZES */

function renderSizes(){

    sizesContainer.innerHTML = '';

    sizes.forEach(size => {

        const tag = document.createElement('div');

        tag.className = 'size-tag';

        tag.innerText = `${size} ×`;

        tag.onclick = () => removeSize(size);

        sizesContainer.appendChild(tag);
    });
}

/* REMOVE SIZE */

function removeSize(size){

    sizes =
        sizes.filter(s => s !== size);

    renderSizes();
}

/* DELETE */

function deleteProduct(id){

    if(confirm('Delete this product?')){

        alert(`Product ${id} deleted`);

        // Add AJAX or fetch here
    }
}

/* SAVE */

form.addEventListener('submit', function(e){

    e.preventDefault();

    const productData = {

        id: editingId,

        name:
            document.getElementById('name').value,

        description:
            document.getElementById('description').value,

        category:
            document.getElementById('category').value,

        price:
            document.getElementById('price').value,

        markup_price:
            document.getElementById('markup_price').value,

        stock_quantity:
            document.getElementById('stock_quantity').value,

        image_url:
            document.getElementById('image_url').value,

        sku:
            document.getElementById('sku').value,

        is_active:
            document.getElementById('is_active').checked,

        sizes_available: sizes
    };

    console.log(productData);

    alert(
        editingId
        ? 'Product updated'
        : 'Product created'
    );

    closeForm();

    // Add AJAX or fetch here
});

/* SEARCH */

document.getElementById('searchInput')
.addEventListener('keyup', function(){

    const value =
        this.value.toLowerCase();

    const rows =
        document.querySelectorAll('#productTable tbody tr');

    rows.forEach(row => {

        const text =
            row.innerText.toLowerCase();

        row.style.display =
            text.includes(value)
            ? ''
            : 'none';
    });
});

/* CLOSE OUTSIDE */

window.onclick = function(event){

    if(event.target === modal){

        closeForm();
    }
}