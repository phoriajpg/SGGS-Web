document.addEventListener('DOMContentLoaded', function() {
  // Different passwords for each option
  const PASSWORDS = {
    'student.html': 'studentSGGS',  // Password for Student option
    'parent.html': 'parentSGGS'     // Password for Parent option
  };

  const optionsSection = document.querySelector('.options-section');
  const passwordSection = document.querySelector('.password-section');
  const options = document.querySelectorAll('.gate-option');
  const passwordInput = document.getElementById('gatePassword');
  const confirmBtn = document.getElementById('confirmBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const errorMessage = document.querySelector('.error-message');
  
  let selectedOption = null;

  // Handle option selection
  options.forEach(option => {
    option.addEventListener('click', function() {
      selectedOption = this.dataset.target;
      optionsSection.style.display = 'none';
      passwordSection.style.display = 'block';
      passwordInput.focus();
    });
  });

  // Handle password confirmation
  confirmBtn.addEventListener('click', function() {
    // Get the correct password for the selected option
    const correctPassword = PASSWORDS[selectedOption];
    
    if (passwordInput.value === correctPassword) {
      window.location.href = selectedOption;
    } else {
      errorMessage.textContent = 'Incorrect password';
      passwordInput.focus();
    }
  });

  // Handle cancel/back
  cancelBtn.addEventListener('click', function() {
    passwordSection.style.display = 'none';
    optionsSection.style.display = 'block';
    passwordInput.value = '';
    errorMessage.textContent = '';
    selectedOption = null;
  });
});