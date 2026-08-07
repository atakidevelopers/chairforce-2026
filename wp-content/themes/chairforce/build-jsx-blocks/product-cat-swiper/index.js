/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src-jsx-blocks/product-cat-swiper/edit.js"
/*!***************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/edit.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/server-side-render */ "@wordpress/server-side-render");
/* harmony import */ var _wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _term_picker__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./term-picker */ "./src-jsx-blocks/product-cat-swiper/term-picker.js");
/* harmony import */ var _term_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./term-utils */ "./src-jsx-blocks/product-cat-swiper/term-utils.js");
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./editor.scss */ "./src-jsx-blocks/product-cat-swiper/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);








function Edit({
  attributes,
  setAttributes
}) {
  const {
    terms = [],
    showArrowsDesktop = true,
    showArrowsMobile = false,
    showProgressBar = true,
    showLabels = true,
    orderBy = 'manual',
    order = 'asc'
  } = attributes;
  const selectedTerms = Array.isArray(terms) ? terms.filter(_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm) : [];
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)({
    className: 'cf-product-cat-swiper-editor'
  });
  const previewAttributes = {
    terms: selectedTerms,
    showArrowsDesktop,
    showArrowsMobile,
    showProgressBar,
    showLabels,
    orderBy,
    order,
    previewMode: true
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Categories', 'chairforce'),
        initialOpen: true,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_term_picker__WEBPACK_IMPORTED_MODULE_4__["default"], {
          terms: selectedTerms,
          onChange: nextTerms => setAttributes({
            terms: nextTerms
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Order by', 'chairforce'),
          value: orderBy,
          options: (0,_term_utils__WEBPACK_IMPORTED_MODULE_5__.getProductCatSwiperOrderByOptions)(),
          onChange: value => setAttributes({
            orderBy: value || 'manual'
          }),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('As selected preserves the token order above. Other options use WordPress term query ordering.', 'chairforce')
        }), orderBy !== 'manual' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Order', 'chairforce'),
          value: order,
          options: (0,_term_utils__WEBPACK_IMPORTED_MODULE_5__.getProductCatSwiperOrderOptions)(),
          onChange: value => setAttributes({
            order: value || 'asc'
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Swiper display', 'chairforce'),
        initialOpen: false,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show arrows on desktop', 'chairforce'),
          checked: showArrowsDesktop,
          onChange: value => setAttributes({
            showArrowsDesktop: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show arrows on mobile', 'chairforce'),
          checked: showArrowsMobile,
          onChange: value => setAttributes({
            showArrowsMobile: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show progress bar', 'chairforce'),
          checked: showProgressBar,
          onChange: value => setAttributes({
            showProgressBar: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show category labels', 'chairforce'),
          checked: showLabels,
          onChange: value => setAttributes({
            showLabels: value
          })
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      ...blockProps,
      children: selectedTerms.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)((_wordpress_server_side_render__WEBPACK_IMPORTED_MODULE_3___default()), {
        block: "chairforce/product-cat-swiper",
        attributes: previewAttributes
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Placeholder, {
        icon: "slides",
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Product Category Swiper', 'chairforce'),
        instructions: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select product categories in the block sidebar to build the swiper.', 'chairforce'),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
          className: "cf-product-cat-swiper-editor__hint",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Use the Categories panel to search and add terms.', 'chairforce')
        })
      })
    })]
  });
}

/***/ },

/***/ "./src-jsx-blocks/product-cat-swiper/save.js"
/*!***************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/save.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
function save() {
  return null;
}

/***/ },

/***/ "./src-jsx-blocks/product-cat-swiper/term-picker.js"
/*!**********************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/term-picker.js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CategoryTermPicker)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_core_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/core-data */ "@wordpress/core-data");
/* harmony import */ var _wordpress_core_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_core_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _term_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./term-utils */ "./src-jsx-blocks/product-cat-swiper/term-utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);







const MIN_SEARCH_LENGTH = 2;
const SEARCH_DEBOUNCE_MS = 300;

/**
 * @param {Array<{ id: number, name: string }>} terms
 * @param {Function} onChange
 */
function CategoryTermPicker({
  terms = [],
  onChange
}) {
  const [search, setSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('');
  const [debouncedSearch, setDebouncedSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)('');
  const debounceTimerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(0);
  const suggestionTermsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(new Map());
  const selectedTerms = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => Array.isArray(terms) ? terms.filter(_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm) : [], [terms]);
  const selectedIds = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => selectedTerms.map(term => term.id), [selectedTerms]);
  const selectedNames = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => selectedTerms.map(term => term.name), [selectedTerms]);
  const {
    records: selectedRecords,
    isResolving: isResolvingSelected
  } = (0,_wordpress_core_data__WEBPACK_IMPORTED_MODULE_4__.useEntityRecords)('taxonomy', 'product_cat', {
    include: selectedIds.length ? selectedIds : [0],
    per_page: Math.max(selectedIds.length, 1),
    hide_empty: false
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    window.clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = window.setTimeout(() => {
      setDebouncedSearch(search.trim());
    }, SEARCH_DEBOUNCE_MS);
    return () => {
      window.clearTimeout(debounceTimerRef.current);
    };
  }, [search]);
  const searchQuery = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => {
    if (debouncedSearch.length < MIN_SEARCH_LENGTH) {
      return null;
    }
    return {
      search: debouncedSearch,
      per_page: 20,
      orderby: 'name',
      order: 'asc',
      hide_empty: false,
      exclude: selectedIds
    };
  }, [debouncedSearch, selectedIds]);
  const {
    searchRecords,
    isSearching
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useSelect)(select => {
    if (!searchQuery) {
      return {
        searchRecords: [],
        isSearching: false
      };
    }
    const records = select(_wordpress_core_data__WEBPACK_IMPORTED_MODULE_4__.store).getEntityRecords('taxonomy', 'product_cat', searchQuery) || [];
    return {
      searchRecords: records,
      isSearching: select(_wordpress_core_data__WEBPACK_IMPORTED_MODULE_4__.store).isResolving('getEntityRecords', ['taxonomy', 'product_cat', searchQuery])
    };
  }, [searchQuery]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (!selectedRecords?.length || !selectedIds.length) {
      return;
    }
    const recordsById = new Map(selectedRecords.map(record => [record.id, record]));
    const syncedTerms = selectedIds.map(id => {
      const record = recordsById.get(id);
      const existing = selectedTerms.find(term => term.id === id);
      if (record) {
        return {
          id: record.id,
          name: record.name
        };
      }
      return existing || null;
    }).filter(_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm);
    const namesChanged = syncedTerms.some((term, index) => {
      return term.name !== selectedTerms[index]?.name;
    });
    if (namesChanged || syncedTerms.length !== selectedTerms.length) {
      onChange(syncedTerms);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- sync labels when core-data records resolve.
  }, [selectedRecords, selectedIds.join(',')]);
  const suggestions = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useMemo)(() => {
    if (!searchRecords.length) {
      return [];
    }
    const termsById = new Map(searchRecords.map(record => [record.id, record]));
    const labels = [];
    searchRecords.forEach(record => {
      if (selectedIds.includes(record.id)) {
        return;
      }
      const term = {
        id: record.id,
        name: record.name
      };
      const label = (0,_term_utils__WEBPACK_IMPORTED_MODULE_5__.getTermPickerLabel)(record, termsById);
      suggestionTermsRef.current.set(label, term);
      suggestionTermsRef.current.set(term.name, term);
      labels.push(label);
    });
    return labels;
  }, [searchRecords, selectedIds]);
  const handleChange = tokenNames => {
    const nextTerms = tokenNames.map(token => {
      const existing = selectedTerms.find(term => term.name === token);
      if ((0,_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm)(existing)) {
        return existing;
      }
      const suggested = suggestionTermsRef.current.get(token);
      return (0,_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm)(suggested) ? suggested : null;
    }).filter(_term_utils__WEBPACK_IMPORTED_MODULE_5__.isValidSelectedTerm);
    onChange(nextTerms);
    setSearch('');
    setDebouncedSearch('');
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [isResolvingSelected && selectedIds.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Spinner, {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.FormTokenField, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Categories', 'chairforce'),
      value: selectedNames,
      suggestions: suggestions,
      onChange: handleChange,
      onInputChange: setSearch,
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Search categories…', 'chairforce'),
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Search and add product categories. Order here is preserved in the swiper.', 'chairforce'),
      __experimentalExpandOnFocus: true,
      maxSuggestions: 20,
      disabled: isResolvingSelected && !selectedTerms.length
    }), isSearching && debouncedSearch.length >= MIN_SEARCH_LENGTH && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("p", {
      className: "cf-product-cat-swiper-editor__searching",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Spinner, {}), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Searching categories…', 'chairforce')]
    })]
  });
}

/***/ },

/***/ "./src-jsx-blocks/product-cat-swiper/term-utils.js"
/*!*********************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/term-utils.js ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   buildHierarchicalTermOptions: () => (/* binding */ buildHierarchicalTermOptions),
/* harmony export */   getProductCatSwiperOrderByOptions: () => (/* binding */ getProductCatSwiperOrderByOptions),
/* harmony export */   getProductCatSwiperOrderOptions: () => (/* binding */ getProductCatSwiperOrderOptions),
/* harmony export */   getTermDepth: () => (/* binding */ getTermDepth),
/* harmony export */   getTermPickerLabel: () => (/* binding */ getTermPickerLabel),
/* harmony export */   isValidSelectedTerm: () => (/* binding */ isValidSelectedTerm)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);


/**
 * @return {Array<{ label: string, value: string }>}
 */
function getProductCatSwiperOrderByOptions() {
  return [{
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('As selected', 'chairforce'),
    value: 'manual'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Menu order', 'chairforce'),
    value: 'menu_order'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Name', 'chairforce'),
    value: 'name'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Slug', 'chairforce'),
    value: 'slug'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Term ID', 'chairforce'),
    value: 'term_id'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Count', 'chairforce'),
    value: 'count'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Description', 'chairforce'),
    value: 'description'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Term group', 'chairforce'),
    value: 'term_group'
  }];
}

/**
 * @return {Array<{ label: string, value: string }>}
 */
function getProductCatSwiperOrderOptions() {
  return [{
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Ascending', 'chairforce'),
    value: 'asc'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Descending', 'chairforce'),
    value: 'desc'
  }];
}

/**
 * Build hierarchical option labels for product_cat terms.
 *
 * @param {Array<{ id: number, name: string, parent?: number }>} terms
 * @param {number} parent
 * @param {number} depth
 * @return {Array<{ id: number, name: string, label: string }>}
 */
function buildHierarchicalTermOptions(terms, parent = 0, depth = 0) {
  if (!Array.isArray(terms) || terms.length === 0) {
    return [];
  }
  return terms.filter(term => (term.parent || 0) === parent).flatMap(term => [{
    id: term.id,
    name: term.name,
    label: `${'— '.repeat(depth)}${term.name}`
  }, ...buildHierarchicalTermOptions(terms, term.id, depth + 1)]);
}

/**
 * @param {{ id: number, name: string, parent?: number }} term
 * @param {Map<number, object>} termsById
 * @return {number}
 */
function getTermDepth(term, termsById) {
  let depth = 0;
  let parentId = term.parent || 0;
  while (parentId > 0 && termsById.has(parentId)) {
    depth += 1;
    parentId = termsById.get(parentId).parent || 0;
  }
  return depth;
}

/**
 * @param {{ id: number, name: string, parent?: number }} term
 * @param {Map<number, object>} termsById
 * @return {string}
 */
function getTermPickerLabel(term, termsById) {
  const depth = getTermDepth(term, termsById);
  return `${'— '.repeat(depth)}${term.name}`;
}

/**
 * @param {unknown} term
 * @return {term is { id: number, name: string }}
 */
function isValidSelectedTerm(term) {
  return !!term && typeof term === 'object' && Number.isFinite(term.id) && term.id > 0 && typeof term.name === 'string' && term.name.length > 0;
}

/***/ },

/***/ "./src-jsx-blocks/product-cat-swiper/editor.scss"
/*!*******************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/editor.scss ***!
  \*******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/core-data"
/*!**********************************!*\
  !*** external ["wp","coreData"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["coreData"];

/***/ },

/***/ "@wordpress/data"
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["data"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "@wordpress/server-side-render"
/*!******************************************!*\
  !*** external ["wp","serverSideRender"] ***!
  \******************************************/
(module) {

module.exports = window["wp"]["serverSideRender"];

/***/ },

/***/ "./src-jsx-blocks/product-cat-swiper/block.json"
/*!******************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/block.json ***!
  \******************************************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"chairforce/product-cat-swiper","version":"1.0.0","title":"Product Category Swiper","category":"woocommerce","description":"Horizontal swiper of selected product categories.","keywords":["woocommerce","category","swiper","carousel"],"textdomain":"chairforce","attributes":{"terms":{"type":"array","default":[]},"showArrowsDesktop":{"type":"boolean","default":true},"showArrowsMobile":{"type":"boolean","default":false},"showProgressBar":{"type":"boolean","default":true},"showLabels":{"type":"boolean","default":true},"orderBy":{"type":"string","default":"manual","enum":["manual","menu_order","name","slug","term_id","count","description","term_group"]},"order":{"type":"string","default":"asc","enum":["asc","desc"]}},"supports":{"html":false,"inserter":true,"reusable":true,"lock":false,"align":["wide","full"]},"editorScript":"file:./index.js","editorStyle":"file:./index.css","render":"file:./render.php"}');

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
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!****************************************************!*\
  !*** ./src-jsx-blocks/product-cat-swiper/index.js ***!
  \****************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edit */ "./src-jsx-blocks/product-cat-swiper/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./save */ "./src-jsx-blocks/product-cat-swiper/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src-jsx-blocks/product-cat-swiper/block.json");




(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  edit: _edit__WEBPACK_IMPORTED_MODULE_1__["default"],
  save: _save__WEBPACK_IMPORTED_MODULE_2__["default"]
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map