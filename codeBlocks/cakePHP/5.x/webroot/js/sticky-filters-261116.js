/*
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 * Licensed under the Apache License, Version 2.0. See the LICENSE and NOTICE files in the project root.
 *
 * Sticky Filters (no library). See AGENTS.md "Sticky Filters".
 *
 * Opt in: add data-sticky-filters to the filter form (one per page).
 * The fixed header is found with data-sticky-filters-header="<selector>" on the form,
 * else Bootstrap's .fixed-top; with no fixed header, the top of the screen is used.
 * It only moves the form: filters submit exactly as they do at the top.
 */
(function () {
    'use strict';

    var DOCKED = 'sticky-filters-docked';

    function headerBottom(form) {
        var selector = form.getAttribute('data-sticky-filters-header') || '.fixed-top';
        var header = document.querySelector(selector);
        if (!header) {
            return 0;
        }
        return Math.max(header.getBoundingClientRect().bottom, 0);
    }

    function init(form) {
        var box = form.closest('.card') || form.parentElement;
        var spacer = document.createElement('div');
        spacer.className = 'sticky-filters-spacer';
        var docked = false;

        // Same left edge and width as the card; body padding keeps the last rows visible
        function place() {
            var rect = box.getBoundingClientRect();
            form.style.left = rect.left + 'px';
            form.style.width = rect.width + 'px';
            document.body.style.paddingBottom = form.offsetHeight + 'px';
        }

        function dock() {
            spacer.style.height = form.offsetHeight + 'px'; // holds the place so the page does not jump
            form.parentNode.insertBefore(spacer, form);
            form.classList.add(DOCKED);
            docked = true;
            place();
        }

        // Back to normal: clear every inline style this script set
        function undock() {
            form.classList.remove(DOCKED);
            form.style.left = '';
            form.style.width = '';
            document.body.style.paddingBottom = '';
            spacer.remove();
            docked = false;
        }

        function update() {
            var anchor = docked ? spacer : form;
            var scrolledPast = anchor.getBoundingClientRect().top < headerBottom(form);

            if (scrolledPast && !docked) {
                dock();
            } else if (!scrolledPast && docked) {
                undock();
            } else if (docked) {
                place();
            }
        }

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('form[data-sticky-filters]');
        if (form) {
            init(form);
        }
    });
})();
