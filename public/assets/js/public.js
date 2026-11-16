document.addEventListener('DOMContentLoaded', () => {
  const faqItems = document.querySelectorAll('[data-faq-toggle]');
  faqItems.forEach(btn => {
    btn.addEventListener('click', () => {
      const body = btn.nextElementSibling;
      if (body) body.style.display = body.style.display === 'none' ? 'block' : 'none';
    });
  });
});
