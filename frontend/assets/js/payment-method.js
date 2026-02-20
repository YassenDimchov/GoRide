$(document).ready(function() {
    $('#stripe-btn').click(function() {
        updatePaymentMethod('online');
    });

    $('#cash-btn').click(function() {
        updatePaymentMethod('cash');
    });

    $(document).on('click', '.pay-now-btn', function() {
        const paymentId = Number($(this).data('payment-id') || 0);
        if (!Number.isInteger(paymentId) || paymentId <= 0) {
            alert('Invalid payment id.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = 'Redirecting...';

        const url = new URL('/GoRide/frontend/api/payments_stripe_checkout.php', window.location.origin);
        url.searchParams.set('id', String(paymentId));

        fetch(url.toString(), {
            method: 'POST',
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json().then((json) => ({ ok: res.ok, status: res.status, json })))
            .then(({ ok, status, json }) => {
                if (!ok) throw new Error(json.message || `Stripe checkout failed (${status})`);
                if (!json.checkout_url) throw new Error('Stripe checkout URL missing.');
                window.location.href = json.checkout_url;
            })
            .catch((error) => {
                alert(error.message || 'Could not start card payment.');
                btn.disabled = false;
                btn.textContent = oldText;
            });
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
    if (!errorMessage) {
        alert(message);
        return;
    }
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
}
