/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js-admin/banner-editor-notice.js"
/*!**********************************************!*\
  !*** ./src/js-admin/banner-editor-notice.js ***!
  \**********************************************/
() {

/**
 * Banner CPT editor guidance — snackbar notice + document panel.
 * Heading blocks are removed server-side for chairforce_banner (Editor_Curation).
 */

wp.domReady(function () {
  if (!wp.plugins?.registerPlugin || !wp.editPost?.PluginDocumentSettingPanel || !wp.data?.useSelect || !wp.element?.useEffect || !wp.element?.createElement) {
    return;
  }
  const {
    __
  } = wp.i18n;
  const {
    registerPlugin
  } = wp.plugins;
  const {
    PluginDocumentSettingPanel
  } = wp.editPost;
  const {
    Notice
  } = wp.components;
  const {
    useEffect,
    createElement: el
  } = wp.element;
  const {
    useSelect
  } = wp.data;
  const BANNER_POST_TYPE = 'chairforce_banner';
  const BANNER_GUIDANCE_PANEL = 'chairforce-banner-editor-guidance/chairforce-banner-guidance';
  const NOTICE_ID = 'chairforce-banner-no-headings-notice';
  const GUIDANCE_MESSAGE = __('Please do not use Heading blocks in banners — they break heading hierarchy on category archive pages. Use Paragraph blocks instead. Apply the "Heading Like" paragraph style (or adjust font size and weight) for prominent banner text.', 'chairforce');
  function ensureBannerGuidancePanelOpen() {
    const editorSelect = wp.data.select('core/editor');
    if (!editorSelect?.isEditorPanelOpened || editorSelect.isEditorPanelOpened(BANNER_GUIDANCE_PANEL)) {
      return;
    }
    wp.data.dispatch('core/editor').toggleEditorPanelOpened(BANNER_GUIDANCE_PANEL);
  }
  function BannerEditorGuidance() {
    const postType = useSelect(select => select('core/editor')?.getCurrentPostType?.(), []);
    useEffect(() => {
      if (BANNER_POST_TYPE !== postType) {
        return;
      }
      wp.data.dispatch('core/notices').createNotice('warning', GUIDANCE_MESSAGE, {
        id: NOTICE_ID,
        isDismissible: true,
        type: 'snackbar'
      });
    }, [postType]);
    useEffect(() => {
      if (BANNER_POST_TYPE !== postType) {
        return undefined;
      }
      ensureBannerGuidancePanelOpen();
      return wp.data.subscribe(ensureBannerGuidancePanelOpen);
    }, [postType]);
    if (BANNER_POST_TYPE !== postType) {
      return null;
    }
    return el(PluginDocumentSettingPanel, {
      name: 'chairforce-banner-guidance',
      title: __('Banner guidelines', 'chairforce'),
      className: 'chairforce-banner-guidance-panel'
    }, el(Notice, {
      status: 'warning',
      isDismissible: false
    }, el('p', null, GUIDANCE_MESSAGE)));
  }
  registerPlugin('chairforce-banner-editor-guidance', {
    render: BannerEditorGuidance
  });
});

/***/ },

/***/ "./src/js-admin/block-styles.js"
/*!**************************************!*\
  !*** ./src/js-admin/block-styles.js ***!
  \**************************************/
() {

wp.domReady(() => {
  wp.blocks.registerBlockStyle('core/buttons', [{
    name: 'full-on-small',
    label: 'Full on Small'
  }]);
  wp.blocks.registerBlockStyle('core/button', [{
    name: 'primary',
    label: 'Primary'
  }, {
    name: 'secondary',
    label: 'Secondary'
  }, {
    name: 'ghost',
    label: 'Ghost'
  }, {
    name: 'light',
    label: 'Light'
  }]);
  wp.blocks.registerBlockStyle('woocommerce/product-price', [{
    name: 'text-price',
    label: 'Price'
  }]);
  wp.blocks.registerBlockStyle('core/paragraph', [{
    name: 'text-eyebrow',
    label: 'Eyebrow'
  }, {
    name: 'text-lead',
    label: 'Lead'
  }, {
    name: 'text-label-nav',
    label: 'Label / Nav'
  }, {
    name: 'text-meta',
    label: 'Meta / Small'
  }, {
    name: 'text-price',
    label: 'Price'
  }, {
    name: 'text-heading-like',
    label: 'Heading Like'
  }]);
  wp.blocks.registerBlockStyle('core/group', [{
    name: 'card',
    label: 'Card'
  }, {
    name: 'narrow',
    label: 'Narrow'
  }]);
});

/***/ },

/***/ "./src/js-admin/button-icons.js"
/*!**************************************!*\
  !*** ./src/js-admin/button-icons.js ***!
  \**************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _lucide_icon_options__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./lucide-icon-options */ "./src/js-admin/lucide-icon-options.js");

/**
 * The icon picker only ever needs to add a `cf-icon-{slug}` class (plus
 * `cf-has-icon`/`cf-icon-left`/`cf-icon-right`) to the Button block - both
 * the editor preview (incl. inside its `<iframe name="editor-canvas">`)
 * and the front end render the glyph purely from CSS via that class,
 * using the Lucide icon font. No DOM/element injection is needed on
 * either side. See assets/css/button-icon-font.css (enqueued globally by
 * Chairforce\Lucide_Icons in lib/class-lucide-icons.php) for the font
 * and the `::before` rules that do the actual rendering.
 *
 * All classes added here use a `cf-` prefix (rather than generic names
 * like `has-icon`/`icon-left`) to avoid colliding with classes from other
 * plugins/themes that use similarly-generic icon-related class names.
 *
 * The picker itself (below) reuses that same font/CSS via a generic
 * `.cf-icon-preview.cf-icon-{slug}` class to show real glyph previews -
 * both for the currently selected icon and for each option in the
 * ComboboxControl's dropdown (via `__experimentalRenderItem`).
 */

const {
  __
} = wp.i18n;
const {
  addFilter
} = wp.hooks;
const {
  createHigherOrderComponent
} = wp.compose;
const {
  Fragment,
  createElement
} = wp.element;
const {
  InspectorControls
} = wp.blockEditor || wp.editor;
const {
  PanelBody,
  SelectControl,
  ComboboxControl
} = wp.components;
const isCoreButtonBlock = blockName => blockName === 'core/button';
const getIconClassNames = (attributes = {}) => {
  if (!attributes.chairforceIcon) {
    return '';
  }
  const iconPosition = attributes.chairforceIconPosition === 'right' ? 'cf-icon-right' : 'cf-icon-left';
  return ['cf-has-icon', iconPosition, `cf-icon-${attributes.chairforceIcon}`].join(' ');
};
const renderIconPreviewSwatch = iconSlug => {
  if (!iconSlug) {
    return createElement('span', {
      className: 'cf-icon-picker__preview-empty'
    });
  }
  return createElement('span', {
    className: `cf-icon-preview cf-icon-${iconSlug}`,
    'aria-hidden': 'true'
  });
};
const renderIconComboboxOption = ({
  item
}) => createElement('span', {
  className: 'cf-icon-picker__option'
}, renderIconPreviewSwatch(item.value), createElement('span', null, item.label));
addFilter('blocks.registerBlockType', 'chairforce/button-icon-attributes', (settings, blockName) => {
  if (!isCoreButtonBlock(blockName)) {
    return settings;
  }
  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      chairforceIcon: {
        type: 'string',
        default: ''
      },
      chairforceIconPosition: {
        type: 'string',
        default: 'left'
      }
    }
  };
});
const withChairforceButtonIconControls = createHigherOrderComponent(BlockEdit => {
  return props => {
    if (!isCoreButtonBlock(props.name)) {
      return createElement(BlockEdit, props);
    }
    const {
      attributes,
      setAttributes
    } = props;
    const iconOptions = _lucide_icon_options__WEBPACK_IMPORTED_MODULE_0__.CHAIRFORCE_LUCIDE_ICON_OPTIONS.map(icon => ({
      value: icon.slug,
      label: icon.label
    }));
    return createElement(Fragment, null, createElement(BlockEdit, props), createElement(InspectorControls, null, createElement(PanelBody, {
      title: __('Button Icon', 'chairforce'),
      initialOpen: false
    }, createElement('div', {
      className: 'cf-icon-picker'
    }, createElement('span', {
      className: 'cf-icon-picker__preview'
    }, renderIconPreviewSwatch(attributes.chairforceIcon)), createElement(ComboboxControl, {
      __next40pxDefaultSize: true,
      className: 'cf-icon-picker__combobox',
      label: __('Icon', 'chairforce'),
      placeholder: __('Search icons…', 'chairforce'),
      value: attributes.chairforceIcon || null,
      options: iconOptions,
      __experimentalRenderItem: renderIconComboboxOption,
      onChange: iconSlug => {
        const nextAttributes = {
          chairforceIcon: iconSlug || ''
        };
        if (iconSlug && !attributes.chairforceIconPosition) {
          nextAttributes.chairforceIconPosition = 'left';
        }
        setAttributes(nextAttributes);
      }
    })), createElement(SelectControl, {
      label: __('Icon Position', 'chairforce'),
      value: attributes.chairforceIconPosition || 'left',
      help: __('Default icon position is left.', 'chairforce'),
      options: [{
        value: 'left',
        label: __('Left', 'chairforce')
      }, {
        value: 'right',
        label: __('Right', 'chairforce')
      }],
      disabled: !attributes.chairforceIcon,
      onChange: nextPosition => {
        setAttributes({
          chairforceIconPosition: nextPosition || 'left'
        });
      }
    }))));
  };
}, 'withChairforceButtonIconControls');
addFilter('editor.BlockEdit', 'chairforce/button-icon-controls', withChairforceButtonIconControls);
const withChairforceButtonBlockListClasses = createHigherOrderComponent(BlockListBlock => {
  return props => {
    if (!isCoreButtonBlock(props.name)) {
      return createElement(BlockListBlock, props);
    }
    const iconClassNames = getIconClassNames(props.attributes);
    const className = [props.className, iconClassNames].filter(Boolean).join(' ');
    return createElement(BlockListBlock, {
      ...props,
      className
    });
  };
}, 'withChairforceButtonBlockListClasses');
addFilter('editor.BlockListBlock', 'chairforce/button-icon-editor-classes', withChairforceButtonBlockListClasses);
addFilter('blocks.getSaveContent.extraProps', 'chairforce/button-icon-save-classes', (extraProps, blockType, attributes) => {
  if (!isCoreButtonBlock(blockType.name)) {
    return extraProps;
  }
  const iconClassNames = getIconClassNames(attributes);
  if (!iconClassNames) {
    return extraProps;
  }
  return {
    ...extraProps,
    className: [extraProps.className, iconClassNames].filter(Boolean).join(' ')
  };
});

/***/ },

/***/ "./src/js-admin/chairforce-icon-block-icons.js"
/*!*****************************************************!*\
  !*** ./src/js-admin/chairforce-icon-block-icons.js ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CHAIRFORCE_ICON_BLOCK_ICONS: () => (/* binding */ CHAIRFORCE_ICON_BLOCK_ICONS)
/* harmony export */ });
/**
 * SVG icon data for the "ChairForce" icon type registered with the Icon
 * Block plugin (outermost/icon-block) - see register-custom-icons.js.
 *
 * This is the *same* curated icon set as CHAIRFORCE_LUCIDE_ICON_OPTIONS
 * (see lucide-icon-options.js, used by the Button block's icon picker),
 * just in the shape the Icon Block plugin's `iconBlock.icons` filter
 * expects (`name`/`title`/`icon`/`categories`/`hasNoIconFill`).
 *
 * Each `icon` value is the raw SVG markup from the `lucide-static` package
 * (node_modules/lucide-static/icons/{slug}.svg), with the root <svg>'s
 * `width`, `height` and `class` attributes stripped - the Icon Block
 * plugin controls sizing itself, and the class attribute is irrelevant
 * once the markup is inlined into block content.
 *
 * `hasNoIconFill: true` is required on every icon here because Lucide
 * icons are stroke-based (`stroke="currentColor"`, `fill="none"`), unlike
 * the plugin's own default icon set, which is fill-based. Without this
 * flag the plugin's color control would try to apply a fill color that
 * fights with `fill="none"`.
 *
 * To add/remove an icon: update CHAIRFORCE_LUCIDE_ICON_OPTIONS in
 * lucide-icon-options.js first (single source of truth for slugs/labels),
 * then add/remove the matching entry below, copying the SVG body from
 * node_modules/lucide-static/icons/{slug}.svg.
 */

const {
  __
} = wp.i18n;
const CHAIRFORCE_ICON_BLOCK_ICONS = [{
  name: 'search',
  title: __('Search', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'shopping-cart',
  title: __('Shopping Cart', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" /><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'file-text',
  title: __('File Text', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" /><path d="M14 2v5a1 1 0 0 0 1 1h5" /><path d="M10 9H8" /><path d="M16 13H8" /><path d="M16 17H8" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'user',
  title: __('User', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'map-pin',
  title: __('Map Pin', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /><circle cx="12" cy="10" r="3" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'phone',
  title: __('Phone', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'heart',
  title: __('Heart', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'plus',
  title: __('Plus', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /><path d="M12 5v14" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'minus',
  title: __('Minus', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'trash-2',
  title: __('Trash 2', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6" /><path d="M14 11v6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'chevron-left',
  title: __('Chevron Left', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'chevron-right',
  title: __('Chevron Right', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'chevron-down',
  title: __('Chevron Down', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'arrow-right',
  title: __('Arrow Right', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'x',
  title: __('X', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'menu',
  title: __('Menu', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16" /><path d="M4 12h16" /><path d="M4 19h16" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'truck',
  title: __('Truck', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'zap',
  title: __('Zap', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'shield-check',
  title: __('Shield Check', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /><path d="m9 12 2 2 4-4" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'smile',
  title: __('Smile', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 14s1.5 2 4 2 4-2 4-2" /><line x1="9" x2="9.01" y1="9" y2="9" /><line x1="15" x2="15.01" y1="9" y2="9" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'package',
  title: __('Package', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" /><path d="M12 22V12" /><polyline points="3.29 7 12 12 20.71 7" /><path d="m7.5 4.27 9 5.15" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'star',
  title: __('Star', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'clock',
  title: __('Clock', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'mail',
  title: __('Mail', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" /><rect x="2" y="4" width="20" height="16" rx="2" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'check',
  title: __('Check', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'check-circle',
  title: __('Check Circle', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335" /><path d="m9 11 3 3L22 4" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'badge-check',
  title: __('Badge Check', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><path d="m9 12 2 2 4-4" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'tag',
  title: __('Tag', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" /><circle cx="7.5" cy="7.5" r=".5" fill="currentColor" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'filter',
  title: __('Filter', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'sliders-horizontal',
  title: __('Sliders Horizontal', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5H3" /><path d="M12 19H3" /><path d="M14 3v4" /><path d="M16 17v4" /><path d="M21 12h-9" /><path d="M21 19h-5" /><path d="M21 5h-7" /><path d="M8 10v4" /><path d="M8 12H3" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}, {
  name: 'grid-2x2',
  title: __('Grid', 'chairforce'),
  icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18" /><path d="M3 12h18" /><rect x="3" y="3" width="18" height="18" rx="2" /></svg>',
  categories: ['chairforce'],
  hasNoIconFill: true
}];

/***/ },

/***/ "./src/js-admin/editor-curation.js"
/*!*****************************************!*\
  !*** ./src/js-admin/editor-curation.js ***!
  \*****************************************/
() {

/**
 * The purpose of the following code is to customize the WordPress Editor by removing unnecessary features and adding custom functionality by tailoring the interface to meet specific needs. By curating the editing environment, you can streamline content creation, reduce clutter, enforce consistency, and ensure that the tools available are relevant.
 */

/**
 * Globally disable RichText formatting options.
 */
wp.domReady(function () {
  const formatsToUnregister = ['core/code', 'core/image', 'core/keyboard', 'core/language'];
  formatsToUnregister.forEach(function (format) {
    wp.richText.unregisterFormatType(format);
  });
});

/**
 * Unregister selected Embed block variations.
 */
wp.domReady(() => {
  const embedVariations = ['animoto', 'dailymotion', 'hulu', 'reddit', 'tumblr', 'vine', 'amazon-kindle', 'cloudup', 'crowdsignal', 'speaker', 'scribd'];
  embedVariations.forEach(variation => {
    wp.blocks.unregisterBlockVariation('core/embed', variation);
  });
});

/**
 * Unregister Image block styles.
 */
wp.domReady(function () {
  wp.blocks.unregisterBlockStyle('core/image', ['rounded']);
});

/**
 * Banner CPT: remove Heading block from the inserter (JS fallback).
 */
wp.domReady(function () {
  if (!wp.data?.subscribe || !wp.blocks?.unregisterBlockType) {
    return;
  }
  let headingRemoved = false;
  const maybeRemoveHeadingBlock = function () {
    if (headingRemoved) {
      return;
    }
    const postType = wp.data.select('core/editor')?.getCurrentPostType?.();
    if (postType !== 'chairforce_banner') {
      return;
    }
    if (wp.blocks.getBlockType('core/heading')) {
      wp.blocks.unregisterBlockType('core/heading');
    }
    headingRemoved = true;
  };
  wp.data.subscribe(maybeRemoveHeadingBlock);
  maybeRemoveHeadingBlock();
});

/***/ },

/***/ "./src/js-admin/index.js"
/*!*******************************!*\
  !*** ./src/js-admin/index.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _block_styles__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./block-styles */ "./src/js-admin/block-styles.js");
/* harmony import */ var _block_styles__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_block_styles__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _editor_curation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./editor-curation */ "./src/js-admin/editor-curation.js");
/* harmony import */ var _editor_curation__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_editor_curation__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _banner_editor_notice__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./banner-editor-notice */ "./src/js-admin/banner-editor-notice.js");
/* harmony import */ var _banner_editor_notice__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_banner_editor_notice__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _button_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./button-icons */ "./src/js-admin/button-icons.js");
/* harmony import */ var _load_more_pagination__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./load-more-pagination */ "./src/js-admin/load-more-pagination.js");
/* harmony import */ var _load_more_pagination__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_load_more_pagination__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _register_custom_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./register-custom-icons */ "./src/js-admin/register-custom-icons.js");







/***/ },

/***/ "./src/js-admin/load-more-pagination.js"
/*!**********************************************!*\
  !*** ./src/js-admin/load-more-pagination.js ***!
  \**********************************************/
() {

/**
 * Extend core/query-pagination with page-1 Load More controls.
 *
 * @see context/plans/3i-load-more-plan.md
 */

const {
  __
} = wp.i18n;
const {
  addFilter
} = wp.hooks;
const {
  createHigherOrderComponent
} = wp.compose;
const {
  Fragment,
  createElement
} = wp.element;
const {
  InspectorControls
} = wp.blockEditor || wp.editor;
const {
  PanelBody,
  ToggleControl,
  TextControl
} = wp.components;
const BLOCK_NAME = 'core/query-pagination';
const withLoadMorePaginationAttributes = (settings, blockName) => {
  if (BLOCK_NAME !== blockName) {
    return settings;
  }
  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      loadMore: {
        type: 'boolean',
        default: false
      },
      loadMoreText: {
        type: 'string',
        default: __('Load More', 'chairforce')
      },
      loadingText: {
        type: 'string',
        default: __('Loading…', 'chairforce')
      }
    }
  };
};
const withLoadMorePaginationControls = createHigherOrderComponent(BlockEdit => {
  return props => {
    if (BLOCK_NAME !== props.name) {
      return createElement(BlockEdit, props);
    }
    const {
      attributes,
      setAttributes
    } = props;
    return createElement(Fragment, null, createElement(BlockEdit, props), createElement(InspectorControls, null, createElement(PanelBody, {
      title: __('Load More', 'chairforce'),
      initialOpen: false
    }, createElement(ToggleControl, {
      __nextHasNoMarginBottom: true,
      label: __('Use Load More on page 1', 'chairforce'),
      help: __('Replaces numbered pagination on the first archive page. Page 2 and later keep standard pagination for SEO.', 'chairforce'),
      checked: !!attributes.loadMore,
      onChange: loadMore => setAttributes({
        loadMore
      })
    }), attributes.loadMore && createElement(TextControl, {
      __next40pxDefaultSize: true,
      label: __('Button label', 'chairforce'),
      value: attributes.loadMoreText || __('Load More', 'chairforce'),
      onChange: loadMoreText => setAttributes({
        loadMoreText
      })
    }), attributes.loadMore && createElement(TextControl, {
      __next40pxDefaultSize: true,
      label: __('Loading label', 'chairforce'),
      value: attributes.loadingText || __('Loading…', 'chairforce'),
      onChange: loadingText => setAttributes({
        loadingText
      })
    }))));
  };
}, 'withLoadMorePaginationControls');
addFilter('blocks.registerBlockType', 'chairforce/load-more-pagination-attributes', withLoadMorePaginationAttributes);
addFilter('editor.BlockEdit', 'chairforce/load-more-pagination-controls', withLoadMorePaginationControls);

/***/ },

/***/ "./src/js-admin/lucide-icon-options.js"
/*!*********************************************!*\
  !*** ./src/js-admin/lucide-icon-options.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CHAIRFORCE_LUCIDE_ICON_OPTIONS: () => (/* binding */ CHAIRFORCE_LUCIDE_ICON_OPTIONS)
/* harmony export */ });
/**
 * Curated list of Lucide icons available in the Button block's icon
 * picker (see src/js-admin/button-icons.js). Intentionally a curated
 * subset - not the full Lucide library (~2,000 icons) - to match the
 * project's Figma design system.
 *
 * Rendering itself is handled purely via CSS - see
 * assets/css/button-icon-font.css (enqueued globally by
 * Chairforce\Lucide_Icons in lib/class-lucide-icons.php) - so this file
 * only needs to expose the slug/label list, not any icon components.
 *
 * To add an icon: add `{ slug, label }` here, then add the matching
 * `.cf-icon-{slug}` rule to assets/css/button-icon-font.css (see that
 * file's header comment for how to look up its codepoint).
 */
const CHAIRFORCE_LUCIDE_ICON_OPTIONS = [{
  slug: 'search',
  label: 'Search'
}, {
  slug: 'shopping-cart',
  label: 'Shopping Cart'
}, {
  slug: 'file-text',
  label: 'File Text'
}, {
  slug: 'user',
  label: 'User'
}, {
  slug: 'map-pin',
  label: 'Map Pin'
}, {
  slug: 'phone',
  label: 'Phone'
}, {
  slug: 'heart',
  label: 'Heart'
}, {
  slug: 'plus',
  label: 'Plus'
}, {
  slug: 'minus',
  label: 'Minus'
}, {
  slug: 'trash-2',
  label: 'Trash 2'
}, {
  slug: 'chevron-left',
  label: 'Chevron Left'
}, {
  slug: 'chevron-right',
  label: 'Chevron Right'
}, {
  slug: 'chevron-down',
  label: 'Chevron Down'
}, {
  slug: 'arrow-right',
  label: 'Arrow Right'
}, {
  slug: 'x',
  label: 'X'
}, {
  slug: 'menu',
  label: 'Menu'
}, {
  slug: 'truck',
  label: 'Truck'
}, {
  slug: 'zap',
  label: 'Zap'
}, {
  slug: 'shield-check',
  label: 'Shield Check'
}, {
  slug: 'smile',
  label: 'Smile'
}, {
  slug: 'package',
  label: 'Package'
}, {
  slug: 'star',
  label: 'Star'
}, {
  slug: 'clock',
  label: 'Clock'
}, {
  slug: 'mail',
  label: 'Mail'
}, {
  slug: 'check',
  label: 'Check'
}, {
  slug: 'check-circle',
  label: 'Check Circle'
}, {
  slug: 'badge-check',
  label: 'Badge Check'
}, {
  slug: 'tag',
  label: 'Tag'
}, {
  slug: 'filter',
  label: 'Filter'
}, {
  slug: 'sliders-horizontal',
  label: 'Sliders Horizontal'
}, {
  slug: 'grid-2x2',
  label: 'Grid'
}, {
  slug: 'list',
  label: 'List'
}, {
  slug: 'eye',
  label: 'Eye'
}];

/***/ },

/***/ "./src/js-admin/register-custom-icons.js"
/*!***********************************************!*\
  !*** ./src/js-admin/register-custom-icons.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _chairforce_icon_block_icons__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./chairforce-icon-block-icons */ "./src/js-admin/chairforce-icon-block-icons.js");
/**
 * Registers the theme's curated Lucide icon set with the Icon Block plugin
 * (outermost/icon-block), so editors can insert them via that block's own
 * inserter/search modal, grouped under a "ChairForce" category - in
 * addition to the plugin's own default icon library.
 *
 * This is a separate icon-picking surface from the Button block's icon
 * picker (see button-icons.js) - they intentionally share the same curated
 * icon set/labels (see chairforce-icon-block-icons.js) but are otherwise
 * independent integrations, since the Icon Block plugin owns its own
 * rendering/attributes and isn't related to `core/button`.
 *
 * @see https://nickdiego.com/adding-custom-icons-to-the-icon-block
 */


wp.domReady(() => {
  const {
    __
  } = wp.i18n;
  const {
    addFilter
  } = wp.hooks;
  function chairforceAddCustomIcons(icons) {
    const customIconCategories = [{
      name: 'chairforce',
      title: __('ChairForce', 'chairforce')
    }];
    const customIconType = [{
      isDefault: true,
      type: 'chairforce',
      title: __('ChairForce', 'chairforce'),
      icons: _chairforce_icon_block_icons__WEBPACK_IMPORTED_MODULE_0__.CHAIRFORCE_ICON_BLOCK_ICONS,
      categories: customIconCategories
    }];
    return [].concat(icons, customIconType);
  }
  addFilter('iconBlock.icons', 'chairforce/icon-block-custom-icons', chairforceAddCustomIcons);
});

/***/ },

/***/ "./src/sass-admin/index.scss"
/*!***********************************!*\
  !*** ./src/sass-admin/index.scss ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	const __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		const cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		const module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			const e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			const getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter/value functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			if(Array.isArray(definition)) {
/******/ 				var i = 0;
/******/ 				while(i < definition.length) {
/******/ 					var key = definition[i++];
/******/ 					var binding = definition[i++];
/******/ 					if(!__webpack_require__.o(exports, key)) {
/******/ 						if(binding === 0) {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, value: definition[i++] });
/******/ 						} else {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, get: binding });
/******/ 						}
/******/ 					} else if(binding === 0) { i++; }
/******/ 				}
/******/ 			} else {
/******/ 				for(var key in definition) {
/******/ 					if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 						Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.hasOwn(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
let __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _sass_admin_index_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./sass-admin/index.scss */ "./src/sass-admin/index.scss");
/* harmony import */ var _js_admin__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./js-admin */ "./src/js-admin/index.js");
/**
 * import SCSS
 */


/**
 * Import Admin JS
 */

})();

/******/ })()
;
//# sourceMappingURL=index.js.map