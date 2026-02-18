function openDriverProfileModal(driverId) {
    const token = '<?= $token ?>';
    const modal = document.getElementById('driver-profile-modal');
    const modalDriverName = document.getElementById('modal-driver-name');
    const modalDriverRating = document.getElementById('modal-driver-rating');
    const modalTotalTrips = document.getElementById('modal-total-trips');
    const modalYearsActive = document.getElementById('modal-years-active');
    const modalResponseTime = document.getElementById('modal-response-time');
    const modalRatingBreakdown = document.getElementById('modal-rating-breakdown');

    fetch(`http://127.0.0.1:8000/api/driver/${driverId}/profile`, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.driver) {
            modalDriverName.textContent = data.driver.name;
            modalDriverRating.textContent = data.driver.average_review || 'No reviews yet';
            modalTotalTrips.textContent = data.driver.total_trips;
            modalYearsActive.textContent = `${data.driver.active_time.years} years, ${data.driver.active_time.months} months, ${data.driver.active_time.days} days`;
            modalResponseTime.textContent = data.driver.average_response_time + ' seconds';
            
            modalRatingBreakdown.innerHTML = '';
            Object.entries(data.driver.rating_breakdown).forEach(([rating, count]) => {
                const ratingElement = document.createElement('div');
                ratingElement.textContent = `${rating} stars: ${count}`;
                modalRatingBreakdown.appendChild(ratingElement);
            });

            modal.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error fetching driver profile:', error);
    });
}

function closeDriverProfileModal() {
    const modal = document.getElementById('driver-profile-modal');
    modal.style.display = 'none';
}
