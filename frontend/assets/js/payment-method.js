$(document).ready(function() {
    $('#stripe-btn').click(function() {
        updatePaymentMethod('online');
    });

    $('#cash-btn').click(function() {
        updatePaymentMethod('cash');
    });
});

function updatePaymentMethod(method) {
    const formData = { method: method };

    fetch('payment-method.php', {
        method: 'POST',
        body: JSON.stringify(formData),
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.reload();
        } else {
            showError('Error updating payment method');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('There was an error with the request.');
    });
}

function showError(message) {
    const errorMessage = document.getElementById('errorMessage');
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
}