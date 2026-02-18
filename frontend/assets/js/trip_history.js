function openReviewModal(rideId) {
  const ride = window.ridesData.find(r => r.id === rideId);

  if (ride) {
    window.ReviewModal.open({
      rideId: rideId,
      ride: ride,
      onSubmitted: (submittedReview) => {
        console.log('Review submitted:', submittedReview);

        const tripCard = document.querySelector(`[data-ride-id="${rideId}"]`);
        if (tripCard) {
          const reviewBtn = tripCard.querySelector(".leave-review-btn");
          if (reviewBtn) reviewBtn.style.display = 'none';

          const reviewTextElement = tripCard.querySelector(".trip-review-text");
          if (reviewTextElement) {
            reviewTextElement.style.display = 'block';
            reviewTextElement.innerHTML = `
              <div class="stars">
                ${generateStars(submittedReview.rating)}
              </div>
              - ${submittedReview.review_text || 'No description'}
            `;
          }
        }
      }
    });
  }
}



function generateStars(rating) {
  let stars = '';
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      stars += '<img src="./assets/images/Icons/star-filled.svg" class="star icon16" alt="Filled star" />';
    } else {
      stars += '<img src="./assets/images/Icons/star-empty.svg" class="star icon16" alt="Empty star" />';
    }
  }
  return stars;
}

function openDriverProfileModal(button) {
  const driverProfile = JSON.parse(button.getAttribute('data-driver-profile'));

  document.getElementById('driverName').innerText = driverProfile.name;
  document.getElementById('averageRating').innerText = driverProfile.average_review;
  document.getElementById('totalTripsInfo').innerText = '• ' + driverProfile.total_trips + ' trips';
  document.getElementById('totalTrips').innerText = driverProfile.total_trips;

  let timeAsDriver = '';
  const years = driverProfile.active_time.years;
  const months = driverProfile.active_time.months;
  const days = driverProfile.active_time.days;

  if (years > 0) {
    timeAsDriver = `${years} years, ${months} months, ${days} days`;
  } else if (months > 0) {
    timeAsDriver = `${months} months, ${days} days`;
  } else {
    timeAsDriver = `${days} days`;
  }

  document.getElementById('yearsActive').innerText = timeAsDriver;

  const average_response_time = Math.round(driverProfile.average_response_time / 60);
  document.getElementById('averageResponseTime').innerText = `${average_response_time} minutes`;

  const totalReviews = Object.values(driverProfile.rating_breakdown).reduce((acc, count) => acc + count, 0);
  for (let i = 1; i <= 5; i++) {
    const ratingPercent = Math.round((driverProfile.rating_breakdown[i] / totalReviews) * 100 || 0);
    document.getElementById(`ratingBar${i}`).style.width = `${ratingPercent}%`;
    document.getElementById(`ratingCount${i}`).innerText = `${ratingPercent.toFixed(0)}%`;
  }

  const modal = document.getElementById('driverProfileModal');
  const overlay = document.createElement('div');
  overlay.classList.add('modal-overlay');
  document.body.appendChild(overlay);

  document.body.style.overflow = 'hidden';

  modal.style.display = 'block';

  const callDriverBtn = document.getElementById('callDriverBtn');
  if (driverProfile.phone) {
    callDriverBtn.href = `tel:${driverProfile.phone}`;
  } else {
    callDriverBtn.disabled = true;
  }

  overlay.addEventListener('click', closeDriverProfileModal);
}

function closeDriverProfileModal() {
  const modal = document.getElementById('driverProfileModal');
  const overlay = document.querySelector('.modal-overlay');
  if (overlay) {
    overlay.remove();
  }
  
  document.body.style.overflow = 'auto';

  modal.style.display = 'none';
}
