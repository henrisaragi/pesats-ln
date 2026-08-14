/*
Template Name: Tailwik - TailwindCSS Admin & Dashboard Template
Author: Themesdesign
Version: 2.0.0
Website: https://themesdesign.in/
Contact: themesdesign.in@gmail.com
File: vendor.js
*/


import '../css/style.css'

import 'preline';

import 'simplebar';

import { createIcons, icons } from 'lucide';
createIcons({ icons });

// Re-run after content injected later (e.g. DataTables AJAX rows) so data-lucide attrs still render.
window.lucideCreateIcons = () => createIcons({ icons });