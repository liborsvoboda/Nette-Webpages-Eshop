/**
 * Global JS
 */

/** Close banner */
const $closeFloatText = document.querySelector('[data-infotext-close]');
$closeFloatText && $closeFloatText.addEventListener('click', function (e) {
	e.preventDefault();
	document.cookie = "floatText=1; path=/";
	document.querySelector($closeFloatText.dataset.infotextClose).remove();
});


/** Slideshow */
const $slideshows = document.querySelectorAll('[data-slideshow]');
Array.from($slideshows).map($slideshow => {
	tns({
		container: $slideshow,
		mode: 'gallery',
		autoplay: true,
		autoplayTimeout: 5000,
		nav: true,
		// autoHeight: true,
		lazyload: true,
		autoplayButtonOutput: false,
		navPosition: 'bottom',
		controls: true
	});
});

/** Carousel */
const $carousels = document.querySelectorAll('[data-carousel]');
Array.from($carousels).map($carousel => {
	tns({
		container: $carousel,
		mode: 'carousel',
		autoplay: true,
		autoplayTimeout: 5000,
		nav: true,
		// autoHeight: true,
		lazyload: true,
		autoplayButtonOutput: false,
		navPosition: 'bottom',
		controls: false
	});
});

/** Expandable text content */
const $expandables = document.querySelectorAll('[data-expandable]');
Array.from($expandables).map($expandable => {
	const $inner = $expandable.querySelector('.expandable__inner');
	const id = $expandable.dataset.expandable;
	const $btn = document.querySelector('[data-expandable-btn="' + id + '"]');
	if ($inner.clientHeight - $expandable.clientHeight > 20) {
		const max = $expandable.clientHeight;
		$btn.addEventListener('click', e => {
			e.preventDefault();
			const h = $expandable.classList.contains('expandable--expanded') ? max : $inner.clientHeight;
			$expandable.style.maxHeight = h + 'px';
			$expandable.classList.toggle('expandable--expanded');
			const label = $btn.innerHTML;
			$btn.innerHTML = $btn.dataset.expandableTitle;
			$btn.dataset.expandableTitle = label;
		});
	} else {
		$expandable.style.maxHeight = $inner.clientHeight + 'px';
		$expandable.classList.remove('expandable');
		$btn.classList.add('d-none');
	}
});


/** Auto form submit */
const initAutoSubmits = ($autoSubmits) => {
	Array.from($autoSubmits).map($autoSubmit => {
		const ev = $autoSubmit.dataset.autoSubmit || 'change';
		$autoSubmit.addEventListener(ev, e => {
			if (ev === 'click') e.preventDefault();
			$($autoSubmit.form).submit(); // jQuery submit to trigger AJAX events
		});
	});
}
initAutoSubmits(document.querySelectorAll('[data-auto-submit]'));


/** Glightbox */
if (typeof GLightbox !== 'undefined') new GLightbox();


/** noUI range slider */
const initRangeSlider = ($sliderWraps) => {
	Array.from($sliderWraps).map(function ($sliderWrap) {
		const limits = $sliderWrap.dataset.range.split(',').map(item => parseFloat(item));
		const $slider = $sliderWrap.querySelector('[data-range-slider]');
		const $start = $sliderWrap.querySelector('[data-range-start]');
		const $end = $sliderWrap.querySelector('[data-range-end]');
		const $startValue = $sliderWrap.querySelector('[data-range-start-value]');
		const $endValue = $sliderWrap.querySelector('[data-range-end-value]');
		noUiSlider.create($slider, {
			start: [parseFloat($start.value), parseFloat($end.value)],
			connect: true,
			step: 1,
			range: {
				min: limits[0],
				max: limits[1]
			}
		});
		$slider.noUiSlider.on('update', values => {
			values = values.map(value => parseFloat(value));;
			var formatter = new Intl.NumberFormat();
			$start.value = values[0];
			$end.value = values[1];
			$startValue.innerHTML = formatter.format(values[0].toFixed(0));
			$endValue.innerHTML = formatter.format(values[1].toFixed(0));
		});
		$slider.noUiSlider.on('change', () => $($slider.closest('form')).submit());
	});
}
initRangeSlider(document.querySelectorAll('[data-range]'));


/** Number input spinner */
const spinNumber = ($btn, more) => {
	const id = $btn.dataset[more ? 'spinMore' : 'spinLess'];
	const $input = document.querySelector('[data-spinner="' + id + '"]');
	const oldVal = $input.value;
	const max = parseFloat($input.max) || null;
	const min = parseFloat($input.min) || null;
	const step = $input.step && $input.step != 'any' ? parseFloat($input.step) : 1;
	const val = parseFloat($input.value) || 1;
	let newVal = val + (more ? 1 : -1) * step;
	if (min !== null && newVal < min) newVal = min;
	if (max !== null && newVal > max) newVal = max;
	$input.value = newVal;

	if (newVal != oldVal) {
		const event = new Event('change');
		$input.dispatchEvent(event);
	}
};
const initSpinNumber = ($buttons, more) => {
	Array.from($buttons).map($button => $button.addEventListener('click', e => spinNumber($button, more)))
}
initSpinNumber(document.querySelectorAll('[data-spin-more]'), true);
initSpinNumber(document.querySelectorAll('[data-spin-less]'), false);


/** Toggle filters */
const $filterToggle = document.querySelector('[data-toggle-filters]');
$filterToggle && $filterToggle.addEventListener('click', e => {
	e.preventDefault();
	if ($filterToggle.getBoundingClientRect().bottom < window.innerHeight) {
		window.scrollBy(0, -(window.innerHeight - $filterToggle.getBoundingClientRect().bottom));
	}
	document.body.classList.toggle('filters--opened');
});


/** FIX bootstrap.native@2.0.27 button bug in Firefox */
const fixButtons = $btns => {
	const $labels = $btns.querySelectorAll('label.btn');
	Array.from($labels).map($label => {
		$label.addEventListener('click', e => {
			Array.from($labels).map($lbl => {
				if ($lbl !== $label) {
					$lbl.classList.remove('focus');
					$lbl.classList.remove('active');
				}
			})
		});
	});
};
const $buttons = document.querySelectorAll('[data-toggle="buttons"]');
Array.from($buttons).map($btns => fixButtons($btns));

/** Disable scroll on collapse */
const $collapses = document.querySelectorAll('[data-toggle="collapse"]');
Array.from($collapses).map($collapse => {
	$collapse.addEventListener('click', e => {
		e.preventDefault();
		e.stopImmediatePropagation();
	});
});


/** Nette extension */
$.nette.ext('dropdowns', {
	start: function (xhr, setting) {
		NProgress.start();

		const disableAppend = setting.nette && setting.nette.ui.dataset.disableAjaxAppend;
		if (disableAppend) {
			const $el = this.getSnippet(disableAppend);
			if ($el) $el.removeAttribute('data-ajax-append');
			this.disabledAppend.indexOf(disableAppend) > -1 || this.disabledAppend.push(disableAppend);
		}
	},
	complete: function (xhr, status, setting) {
		NProgress.done();

		for (let i = 0; i < this.disabledAppend.length; i++) {
			const id = this.disabledAppend[i];
			const $el = this.getSnippet(id);
			if ($el) $el.dataset.ajaxAppend = '';
			this.disabledAppend = this.disabledAppend.filter(key => key != id);
		}
	}
}, {
	disabledAppend: [],
	getSnippet: (id) => document.getElementById('snippet--' + id)
});


// Open modal after product added to cart
$.nette.ext('cartModal', {
	success: function (payload) {
		if (payload.addedToCart === true) {
			const $cartModal = document.getElementById('cartModal');
			const cartModal = new Modal($cartModal);
			cartModal.show();
		}
		if (payload.addedToCartItem) {
			const item = payload.addedToCartItem;
			const prod = item.product;
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({
				currency: 'EUR',
				event: 'addToCart',
				product: {
					id: prod.id,
					name: prod.name,
					price: prod.price,
					quantity: item.amount
				}
			});
			window.dataLayer.push({
				event: 'add_to_cart',
				currency: 'EUR',
				value: parseFloat(prod.price) * parseFloat(item.amount),
				items: [
					{
						id: prod.id,
						name: prod.name,
						price: prod.price,
						quantity: item.amount,
						google_business_vertical: 'retail'
					}
				]
			});
		}
	}
});


// Reset Bootstrap.native and other functions after snippets has been updated
$.nette.ext('snippets').afterQueue.add($el => {
	$el.find('[data-toggle]').each(function () {
		if ($(this).data('toggle') === 'dropdown') {
			new Dropdown($(this)[0]);
		}
		if ($(this).data('toggle') === 'modal') {
			new Modal($(this)[0]);
		}
		if ($(this).data('toggle') === 'buttons') {
			new Button($(this)[0]);
			fixButtons($(this)[0]);
		}
	});
	$el.find('[data-auto-submit]').each(function () {
		initAutoSubmits([this]);
	});
	$el.find('[data-range]').each(function () {
		initRangeSlider([this]);
	});
	$el.find('[data-spin-more]').each(function () {
		initSpinNumber([this], true);
	});
	$el.find('[data-spin-less]').each(function () {
		initSpinNumber([this], false);
	});
});