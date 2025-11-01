let current = 0;
    const slides = document.querySelectorAll('.slide');

    function showSlide(index) {
      slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
      });
    }

    setInterval(() => {
      current = (current + 1) % slides.length;
      showSlide(current);
    }, 5000);

    const carousel = document.querySelector('.carousel');

    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        carousel.classList.add('shrink-logo');
      } else {
        carousel.classList.remove('shrink-logo');
      }
    });

    const texts = document.querySelectorAll('.scroll-text');
    window.addEventListener('scroll', () => {
      texts.forEach((text) => {
        const rect = text.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
          text.classList.add('visible');
        } else {
          text.classList.remove('visible');
        }
      });
    });

    function setContent(value) {
      const display = document.getElementById('content-display');
      switch (value) {
        case 'Integrity':
          display.innerHTML = '<p>To consistent strength to adhere to a code of moral reasoning in spite of the personal discomfort this might bring. We must show moral strength and consistency in matters personal, academic or social, to always tell the truth, be sincere; refrain from lying, cheating or stealing; resist social pressure to do things you think are wrong, not to betray a trust or withhold important information in relationship of trust.</p>';
          break;
        case 'Compassion':
          display.innerHTML = '<p>Compassion is a desire to alleviate or reduce the suffering of another and to show special kindness to those who suffer. Compassion may lead one to feel empathy with another person. Compassion is often characterized through actions, where in a person acting with compassion will seek to aid those they feel compassionate for. We must endeavour to put ourselves in the shoes of others before passing judgement, treating someone harshly, or speaking cruelly either to someones face or behind his or her back.</p>';
          break;
        case 'Respect':
          display.innerHTML = '<p>The unqualified high regards for others as well as respect for ourselves and others; both as the people we are and the people we strive to become. Be kind, caring, tolerant, appreciative and accepting of individual difference; be courteous and polite; judge all people based on their merits, respect the rights of individuals to make decisions about their own lives; show self respect; treat others as you would want to be treated; have regard for the property of others as well as for the environment.</p>';
          break;
        case 'Responsibility':
          display.innerHTML = '<p>Being morally or legally accountable, reliable and worthy of trust. We must hold no one but ourselves responsible for our own actions or inactions, as know we are free to choose them. Accept responsibility for the consequences of your choices; think before your act and consider how others will be affected by your actions; dont make excuses, blame others for your mistakes or take credit for others achievements, set a good example fo others at all times and practice self-discipline.</p>';
          break;
        default:
          display.innerHTML = '<p>Click a Core Value to see its description.</p>';
      }
  
      // Update active state
      document.querySelectorAll('.selector').forEach(btn => {
        btn.classList.remove('active');
        if(btn.textContent === value) {
          btn.classList.add('active');
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Set your password here
      const CORRECT_PASSWORD = "secret123";
      const options = document.querySelectorAll('.gate-option');
      const passwordPrompt = document.querySelector('.password-prompt');
      const passwordInput = document.getElementById('gatePassword');
      const confirmBtn = document.getElementById('confirmBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const errorMessage = document.querySelector('.error-message');
  
      let selectedOption = null;

      // Handle option selection
      options.forEach(option => {
        option.addEventListener('click', function() {
          selectedOption = this.dataset.target;
          passwordPrompt.style.display = 'block';
          // Scroll to show password prompt
          passwordPrompt.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
      });

      // Handle password confirmation
      confirmBtn.addEventListener('click', function() {
        if (passwordInput.value === CORRECT_PASSWORD) {
          window.location.href = selectedOption;
        } else {
          errorMessage.textContent = 'Incorrect password';
          passwordInput.focus();
        }
      });

      // Handle cancel
      cancelBtn.addEventListener('click', function() {
        passwordPrompt.style.display = 'none';
        passwordInput.value = '';
        errorMessage.textContent = '';
      });
    });

    // Counter animation
    function animateCounter(element, start, end, duration) {
      let startTimestamp = null;
      const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };
      window.requestAnimationFrame(step);
    }

    // Start animation when page loads
    document.addEventListener('DOMContentLoaded', function() {
      const counter = document.getElementById('yearsCounter');
      animateCounter(counter, 1, 140, 2000); // Count from 1 to 140 in 2 seconds
    });