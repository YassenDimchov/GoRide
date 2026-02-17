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