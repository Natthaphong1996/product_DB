document.addEventListener('DOMContentLoaded', function() {
    $('.select2').select2();
    applyColorClasses();
    setTimeout(function() {
        $("#alertMessage").alert('close');
    }, 7000);
});

function setType(type) {
    document.getElementById('type').value = type;
}

function submitForm() {
    document.getElementById('dataForm').submit();
}

function submitAddProductForm() {
    document.getElementById('addProductForm').submit();
}

function submitDeleteProductForm() {
    document.getElementById('deleteProductForm').submit();
}

function applyColorClasses() {
    const items = document.querySelectorAll('.list-group-item');
    items.forEach(item => {
        const quantity = parseInt(item.dataset.quantity);
        const low = parseInt(item.dataset.low);
        
        if (quantity > low) {
            item.classList.add('quantity-high');
        } else if (quantity <= low && quantity > low / 2) {
            item.classList.add('quantity-medium');
        } else {
            item.classList.add('quantity-low');
        }
    });
}
