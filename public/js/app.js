'use strict';
for (const form of document.querySelectorAll('[data-purchase-form]')) {
    const quantity = form.querySelector('[name="quantity"]');
    const output = form.querySelector('[data-order-total]');
    const decrease = form.querySelector('[data-quantity-decrease]');
    const increase = form.querySelector('[data-quantity-increase]');
    const minimum = Number(quantity.min);
    const maximum = Number(quantity.max);
    const [whole, fraction = ''] = form.dataset.unitPrice.split('.');
    const unit = BigInt(whole) * 1000n + BigInt(fraction.padEnd(3, '0'));
    const updateTotal = () => {
        const current = quantity.valueAsNumber;
        decrease.disabled = Number.isFinite(current) && current <= minimum;
        increase.disabled = Number.isFinite(current) && current >= maximum;
        if (!/^\d+$/.test(quantity.value) || !quantity.validity.valid) {
            output.textContent = '—';
            return;
        }
        const total = unit * BigInt(quantity.value);
        output.textContent = `${total / 1000n}.${String(total % 1000n).padStart(3, '0')}`;
    };
    const changeQuantity = (direction) => {
        const current = quantity.valueAsNumber;
        const next = Number.isFinite(current)
            ? (direction > 0 ? Math.floor(current) + 1 : Math.ceil(current) - 1)
            : minimum;
        quantity.value = String(Math.min(maximum, Math.max(minimum, next)));
        quantity.dispatchEvent(new Event('input', { bubbles: true }));
    };
    decrease.addEventListener('click', () => changeQuantity(-1));
    increase.addEventListener('click', () => changeQuantity(1));
    quantity.addEventListener('input', updateTotal);
    updateTotal();
}
for (const form of document.querySelectorAll('form')) {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');
        if (submit && form.checkValidity()) {
            submit.dataset.originalText = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'Please wait…';
        }
    });
}
window.addEventListener('pageshow', () => {
    for (const button of document.querySelectorAll('button[type="submit"][disabled]')) {
        button.disabled = false;
        button.textContent = button.dataset.originalText;
    }
});
