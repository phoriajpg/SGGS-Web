document.querySelectorAll('.polaroid').forEach(polaroid => {
    polaroid.addEventListener('mousedown', dragStart);

    function dragStart(e) {
        e.preventDefault();

        // Bring the dragged polaroid to front
        polaroid.style.zIndex = 1000;

        let shiftX = e.clientX - polaroid.getBoundingClientRect().left;
        let shiftY = e.clientY - polaroid.getBoundingClientRect().top;

        function moveAt(pageX, pageY) {
            polaroid.style.left = pageX - shiftX + 'px';
            polaroid.style.top = pageY - shiftY + 'px';
        }

        function onMouseMove(e) {
            moveAt(e.pageX, e.pageY);
        }

        document.addEventListener('mousemove', onMouseMove);

        document.addEventListener('mouseup', () => {
            document.removeEventListener('mousemove', onMouseMove);
            polaroid.style.zIndex = ''; // Reset z-index when done
        }, { once: true });
    }

    // Prevent default drag behavior of images
    polaroid.ondragstart = () => false;
});



// Filter functionality
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        const cards = document.querySelectorAll('.award-card');
        
        cards.forEach(card => {
          if (filter === 'all' || card.dataset.category === filter) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });