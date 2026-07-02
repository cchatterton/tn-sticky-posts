(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initialise(root) {
        var items = Array.prototype.slice.call(root.querySelectorAll('[data-announcement-item]'));
        var speed = Math.max(2000, parseInt(root.getAttribute('data-speed') || '5000', 10));
        var pauseHover = root.getAttribute('data-pause-hover') !== 'false';
        var current = 0;
        var timer = null;
        var transitionMs = 300;

        if (!items[current].classList.contains('is-active')) {
            items[current].classList.add('is-active');
        }

        if (reduceMotion) {
            root.classList.add('is-reduced-motion');
            root.classList.add('is-initialised');
            return;
        }

        if (items.length <= 1 || root.classList.contains('sticky-announcements--none')) {
            root.classList.add('is-initialised');
            return;
        }

        function updateHeight() {
            var height = items[current].getBoundingClientRect().height || items[current].scrollHeight;

            if (height > 0) {
                root.style.setProperty('--sticky-announcements-height', height + 'px');
            }
        }

        function show(index) {
            var previous = items[current];
            var nextItem = items[index];

            if (previous === nextItem) {
                return;
            }

            updateHeight();
            previous.classList.add('is-exiting');
            previous.classList.remove('is-active');
            current = index;

            window.requestAnimationFrame(function () {
                nextItem.classList.add('is-active');
                updateHeight();
            });

            window.setTimeout(function () {
                previous.classList.remove('is-exiting');
            }, transitionMs);
        }

        function next() {
            show((current + 1) % items.length);
        }

        function start() {
            if (timer) {
                return;
            }

            timer = window.setInterval(next, speed);
        }

        function stop() {
            if (!timer) {
                return;
            }

            window.clearInterval(timer);
            timer = null;
        }

        if (pauseHover) {
            root.addEventListener('mouseenter', stop);
            root.addEventListener('mouseleave', start);
        }

        window.addEventListener('resize', updateHeight);

        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (!root.contains(document.activeElement)) {
                    start();
                }
            }, 0);
        });

        updateHeight();
        root.classList.add('is-initialised');

        window.requestAnimationFrame(function () {
            updateHeight();
            root.classList.add('is-ready');
            start();
        });
    }

    function ready() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-sticky-announcements]'), initialise);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
}());
