import { __ } from '@wordpress/i18n';
import { FormTokenField, Spinner } from '@wordpress/components';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';

import {
	getTermPickerLabel,
	isValidSelectedTerm,
} from './term-utils';

const MIN_SEARCH_LENGTH = 2;
const SEARCH_DEBOUNCE_MS = 300;

/**
 * @param {Array<{ id: number, name: string }>} terms
 * @param {Function} onChange
 */
export default function CategoryTermPicker( { terms = [], onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const [ debouncedSearch, setDebouncedSearch ] = useState( '' );
	const debounceTimerRef = useRef( 0 );
	const suggestionTermsRef = useRef( new Map() );

	const selectedTerms = useMemo(
		() => ( Array.isArray( terms ) ? terms.filter( isValidSelectedTerm ) : [] ),
		[ terms ]
	);
	const selectedIds = useMemo(
		() => selectedTerms.map( ( term ) => term.id ),
		[ selectedTerms ]
	);
	const selectedNames = useMemo(
		() => selectedTerms.map( ( term ) => term.name ),
		[ selectedTerms ]
	);

	const { records: selectedRecords, isResolving: isResolvingSelected } =
		useEntityRecords( 'taxonomy', 'product_cat', {
			include: selectedIds.length ? selectedIds : [ 0 ],
			per_page: Math.max( selectedIds.length, 1 ),
			hide_empty: false,
		} );

	useEffect( () => {
		window.clearTimeout( debounceTimerRef.current );
		debounceTimerRef.current = window.setTimeout( () => {
			setDebouncedSearch( search.trim() );
		}, SEARCH_DEBOUNCE_MS );

		return () => {
			window.clearTimeout( debounceTimerRef.current );
		};
	}, [ search ] );

	const searchQuery = useMemo( () => {
		if ( debouncedSearch.length < MIN_SEARCH_LENGTH ) {
			return null;
		}

		return {
			search: debouncedSearch,
			per_page: 20,
			orderby: 'name',
			order: 'asc',
			hide_empty: false,
			exclude: selectedIds,
		};
	}, [ debouncedSearch, selectedIds ] );

	const { searchRecords, isSearching } = useSelect(
		( select ) => {
			if ( ! searchQuery ) {
				return {
					searchRecords: [],
					isSearching: false,
				};
			}

			const records =
				select( coreStore ).getEntityRecords(
					'taxonomy',
					'product_cat',
					searchQuery
				) || [];

			return {
				searchRecords: records,
				isSearching: select( coreStore ).isResolving(
					'getEntityRecords',
					[ 'taxonomy', 'product_cat', searchQuery ]
				),
			};
		},
		[ searchQuery ]
	);

	useEffect( () => {
		if ( ! selectedRecords?.length || ! selectedIds.length ) {
			return;
		}

		const recordsById = new Map(
			selectedRecords.map( ( record ) => [ record.id, record ] )
		);

		const syncedTerms = selectedIds
			.map( ( id ) => {
				const record = recordsById.get( id );
				const existing = selectedTerms.find( ( term ) => term.id === id );

				if ( record ) {
					return {
						id: record.id,
						name: record.name,
					};
				}

				return existing || null;
			} )
			.filter( isValidSelectedTerm );

		const namesChanged = syncedTerms.some( ( term, index ) => {
			return term.name !== selectedTerms[ index ]?.name;
		} );

		if ( namesChanged || syncedTerms.length !== selectedTerms.length ) {
			onChange( syncedTerms );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps -- sync labels when core-data records resolve.
	}, [ selectedRecords, selectedIds.join( ',' ) ] );

	const suggestions = useMemo( () => {
		if ( ! searchRecords.length ) {
			return [];
		}

		const termsById = new Map(
			searchRecords.map( ( record ) => [ record.id, record ] )
		);
		const labels = [];

		searchRecords.forEach( ( record ) => {
			if ( selectedIds.includes( record.id ) ) {
				return;
			}

			const term = {
				id: record.id,
				name: record.name,
			};
			const label = getTermPickerLabel( record, termsById );

			suggestionTermsRef.current.set( label, term );
			suggestionTermsRef.current.set( term.name, term );
			labels.push( label );
		} );

		return labels;
	}, [ searchRecords, selectedIds ] );

	const handleChange = ( tokenNames ) => {
		const nextTerms = tokenNames
			.map( ( token ) => {
				const existing = selectedTerms.find(
					( term ) => term.name === token
				);

				if ( isValidSelectedTerm( existing ) ) {
					return existing;
				}

				const suggested = suggestionTermsRef.current.get( token );

				return isValidSelectedTerm( suggested ) ? suggested : null;
			} )
			.filter( isValidSelectedTerm );

		onChange( nextTerms );
		setSearch( '' );
		setDebouncedSearch( '' );
	};

	return (
		<>
			{ isResolvingSelected && selectedIds.length > 0 && (
				<Spinner />
			) }
			<FormTokenField
				label={ __( 'Categories', 'chairforce' ) }
				value={ selectedNames }
				suggestions={ suggestions }
				onChange={ handleChange }
				onInputChange={ setSearch }
				placeholder={ __( 'Search categories…', 'chairforce' ) }
				help={ __(
					'Search and add product categories. Order here is preserved in the swiper.',
					'chairforce'
				) }
				__experimentalExpandOnFocus
				maxSuggestions={ 20 }
				disabled={ isResolvingSelected && ! selectedTerms.length }
			/>
			{ isSearching && debouncedSearch.length >= MIN_SEARCH_LENGTH && (
				<p className="cf-product-cat-swiper-editor__searching">
					<Spinner />
					{ __( 'Searching categories…', 'chairforce' ) }
				</p>
			) }
		</>
	);
}
