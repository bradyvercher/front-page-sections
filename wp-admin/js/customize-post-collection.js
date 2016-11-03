(function( wp, $ ) {

	if ( ! wp || ! wp.customize ) { return; }

	var api = wp.customize;

	api.PostCollection = api.PostCollection || {};

	api.PostModel = Backbone.Model.extend({
		defaults: {
			sortableOrder: 0,
			title: ''
		}
	});

	api.PostsCollection = Backbone.Collection.extend({
		model: api.PostModel,
		comparator: 'sortableOrder'
	});

	api.Drawer = api.Class.extend({
		type: 'drawer',

		initialize: function( id, options ) {
			var drawer = this;

			_.extend( this, options || {} );
			this.id = id;

			_.bindAll( this, 'collapseOtherDrawers' );
			this.container = $( '<div class="customize-drawer" />' );

			this.deferred = {
				embedded: new $.Deferred()
			};

			this.control = new api.Value();
			this.control.set( options.control );

			this.expanded = new api.Value();
			this.expanded.set( false );
			this.expanded.bind( this.collapseOtherDrawers );

			drawer.embed();
			drawer.deferred.embedded.done(function () {
				drawer.ready();
			});

			// Collapse the drawer when the control's section is collapsed.
			api.control( this.control(), function( control ) {
				api.section( control.section() ).expanded.bind(function( isExpanded ) {
					if ( ! isExpanded ) {
						drawer.collapse();
					}
				});
			});
		},

		embed: function () {
			$( '.wp-full-overlay' ).append( this.container );

			this.view = new wp.Backbone.View({
				el: this.container
			});

			this.view.views.add(
				new this.TitleView({
					drawer: this
				})
			);

			this.deferred.embedded.resolve();
		},

		ready: function() {},

		collapse: function() {
			this.expanded.set( false );
			this.container.removeClass( 'is-open' );
			$( document.body ).removeClass( 'drawer-is-open' );
			api.control( this.control() ).container.removeClass( 'is-drawer-open' );
		},

		expand: function() {
			this.expanded.set( true );
			this.container.addClass( 'is-open' );
			$( document.body ).addClass( 'drawer-is-open' );
			api.control( this.control() ).container.addClass( 'is-drawer-open' );
		},

		toggle: function() {
			if ( this.expanded() ) {
				this.collapse();
			} else {
				this.expand();
			}
		},

		collapseOtherDrawers: function( isExpanded ) {
			if ( isExpanded ) {
				api.drawer.each(function( drawer ) {
					if ( drawer.id !== this.id ) {
						drawer.collapse();
					}
				}, this );

				if ( this.expanded() ) {
					$( document.body ).addClass( 'drawer-is-open' );
				}
			}
		},

		TitleView: wp.Backbone.View.extend({
			className: 'customize-drawer-title customize-section-title',
			template: wp.template( 'customize-drawer-title' ),

			events: {
				'click .customize-section-back': 'collapseDrawer'
			},

			initialize: function( options ) {
				this.drawer = options.drawer;
			},

			render: function() {
				this.$el.html( this.template( this.drawer.labels ) );
				return this;
			},

			collapseDrawer: function( e ) {
				e.preventDefault();
				this.drawer.collapse();
			}
		})
	});

	api.PostSearchDrawer = api.Drawer.extend({
		type: 'post-search-drawer',

		ready: function() {
			var drawer = this;

			this.results = new api.PostsCollection();

			this.state = new Backbone.Model({
				notice: ''
			});

			this.view.views.add([
				new this.SearchFormView({
					collection: this.results,
					drawer: this
				}),
				new this.NoticeView({
					drawer: this
				}),
				new this.SearchResultsView({
					collection: this.results,
					drawer: this,
					selection: this.selection
				})
			]);

			this.expanded.bind(function( isExpanded ) {
				if ( isExpanded && drawer.results.length < 1 ) {
					drawer.search();
				}
			});
		},

		search: function( query ) {
			var drawer = this;

			return wp.ajax.post( 'fps_find_posts', {
				ps: query,
				post_types: this.postTypes,
				post_status: 'publish',
				format: 'json',
				_ajax_nonce: this.searchNonce
			}).done(function( response ) {
				drawer.results.reset( response );
				drawer.state.set( 'notice', '' );
			}).fail(function( response ) {
				drawer.results.reset();
				drawer.state.set( 'notice', response );
			});
		},

		NoticeView: wp.Backbone.View.extend({
			tagName: 'div',
			className: 'customize-drawer-notice',

			initialize: function( options ) {
				this.drawer = options.drawer;
				this.listenTo( this.drawer.state, 'change:notice', this.render );
			},

			render: function() {
				var notice = this.drawer.state.get( 'notice' );
				this.$el.toggle( !! notice.length ).text( notice );
				return this;
			}
		}),

		SearchFormView: wp.Backbone.View.extend({
			tagName: 'div',
			className: 'search-group',
			template: wp.template( 'search-group' ),

			events: {
				'click .clear-results' : 'clearResults',
				'input input': 'search'
			},

			initialize: function( options ) {
				this.collection = options.collection;
				this.drawer = options.drawer;

				this.listenTo( this.collection, 'add remove reset', this.updateClearResultsVisibility );
			},

			render: function() {
				this.$el.html( this.template({ labels: this.drawer.labels }) );
				this.$clearResults = this.$( '.clear-results' );
				this.$field = this.$( '.search-group-field' );
				this.$spinner = this.$el.append( '<span class="search-group-spinner spinner" />' ).find( '.spinner' );
				this.updateClearResultsVisibility();
				return this;
			},

			clearResults: function() {
				this.collection.reset();
				this.$field.val( '' ).trigger( 'input' ).focus();
			},

			search: function() {
				var view = this;

				this.$el.addClass( 'is-searching' );
				this.$spinner.addClass( 'is-active' );

				clearTimeout( this.timeout );
				this.timeout = setTimeout(function() {
					view.drawer.search( view.$field.val() )
						.always(function() {
							view.$el.removeClass( 'is-searching' );
							view.$spinner.removeClass( 'is-active' );
						});
				}, 300 );
			},

			updateClearResultsVisibility: function() {
				this.$clearResults.toggleClass( 'is-visible', !! this.collection.length && '' !== this.$field.val() );
			}
		}),

		SearchResultsView: wp.Backbone.View.extend({
			tagName: 'div',
			className: 'search-results',

			initialize: function( options ) {
				this.collection = options.collection;
				this.drawer = options.drawer;
				this.selection = options.selection;

				this.listenTo( this.collection, 'reset', this.render );
			},

			render: function() {
				this.$list = this.$el.html( '<ul />' ).find( 'ul' );
				this.$el.toggleClass( 'hide-type-label', 1 === this.drawer.postTypes.length );

				if ( this.collection.length ) {
					this.collection.each( this.addItem, this );
				} else {
					this.$el.empty();
				}

				return this;
			},

			addItem: function( model ) {
				this.views.add( 'ul', new this.drawer.SearchResultView({
					drawer: this.drawer,
					model: model,
					selection: this.selection
				}));
			}
		}),

		SearchResultView: wp.Backbone.View.extend({
			tagName: 'li',
			className: 'search-results-item',
			template: wp.template( 'search-result' ),

			events: {
				'click': 'addItem'
			},

			initialize: function( options ) {
				this.drawer = options.drawer;
				this.model = options.model;
				this.selection = options.selection;

				this.listenTo( this.selection, 'add remove reset', this.updateSelectedClass );
			},

			render: function() {
				var data = _.extend( this.model.toJSON(), {
					labels: this.drawer.labels
				});

				this.$el.html( this.template( data ) );
				this.updateSelectedClass();

				return this;
			},

			addItem: function() {
				this.selection.add( this.model );
			},

			updateSelectedClass: function() {
				this.$el.toggleClass( 'is-selected', !! this.selection.get( this.model.id ) );
			}
		})
	});

	api.PostCollectionControl = api.Control.extend({
		ready: function() {
			var control = this;

			this.posts = new api.PostsCollection( this.params.posts );
			delete this.params.posts;

			this.drawer = new api.PostSearchDrawer( this.id, {
				control: this.id,
				labels: _.extend( this.params.labels, {
					customizeAction: this.params.labels.addPosts,
					title: this.params.label
				}),
				postTypes: this.params.postTypes,
				searchNonce: this.params.searchNonce,
				selection: this.posts
			});
			api.drawer.add( this.id, this.drawer );

			// Update the setting when the post collection is modified.
			this.posts.on( 'add remove reset sort', function() {
				var ids = this.posts.pluck( 'id' );

				if ( this.setting() !== ids ) {
					this.setting.set( ids );
				}
			}, this );

			if ( this.params.includeFrontPage ) {
				// Add the front page when it changes.
				api( 'page_on_front', function( setting ) {
					setting.bind( _.bind( control.onPageOnFrontChange, control ) );
				});
			}

			this.view = new wp.Backbone.View({
				el: this.container
			});

			this.view.views.add([
				new this.ListView({
					collection: this.posts,
					control: this
				}),
				new this.AddNewItemButtonView({
					control: this
				})
			]);
		},

		onPageOnFrontChange: function( value ) {
			var id = parseInt( value, 10 ),
				posts = this.posts.toJSON(),
				pageOnFrontControl = api.control( 'page_on_front' );

			if ( id > 1 && ! this.posts.findWhere({ id: id }) ) {
				posts.unshift({
					id: id,
					// @todo Find a better way to grab this title.
					title: pageOnFrontControl.container.find( 'option:selected' ).text()
				});
			}

			// Remove the previous front page if it was the only post in the list.
			if ( 2 === posts.length ) {
				posts = posts.shift();
			}

			// Reset the collection to re-render the view.
			this.posts.reset( posts );
		},

		AddNewItemButtonView: wp.Backbone.View.extend({
			className: 'add-new-item button button-secondary alignright',
			tagName: 'button',

			events: {
				click: 'toggleDrawer'
			},

			initialize: function( options ) {
				this.control = options.control;
			},

			render: function() {
				this.$el.text( this.control.params.labels.addPosts );
				return this;
			},

			toggleDrawer: function( e ) {
				e.preventDefault();
				this.control.drawer.toggle();
			}
		}),

		ListView: wp.Backbone.View.extend({
			className: 'wp-items-list',
			tagName: 'ol',

			initialize: function( options ) {
				var view = this;

				this.control = options.control;

				this.listenTo( this.collection, 'add', this.addItem );
				this.listenTo( this.collection, 'add remove', this.updateOrder );
				this.listenTo( this.collection, 'reset', this.render );
			},

			render: function() {
				this.$el.empty();
				this.collection.each( this.addItem, this );
				this.initializeSortable();
				return this;
			},

			initializeSortable: function() {
				this.$el.sortable({
					axis: 'y',
					delay: 150,
					forceHelperSize: true,
					forcePlaceholderSize: true,
					opacity: 0.6,
					start: function( e, ui ) {
						ui.placeholder.css( 'visibility', 'visible' );
					},
					update: _.bind(function() {
						this.updateOrder();
					}, this )
				});
			},

			addItem: function( item ) {
				var itemView = new this.control.ItemView({
					control: this.control,
					model: item,
					parent: this
				});

				this.$el.append( itemView.render().el );
			},

			moveDown: function( model ) {
				var index = this.collection.indexOf( model ),
					$items = this.$el.children();

				if ( index < this.collection.length - 1 ) {
					$items.eq( index ).insertAfter( $items.eq( index + 1 ) );
					this.updateOrder();
					wp.a11y.speak( this.control.params.labels.movedDown );
				}
			},

			moveUp: function( model ) {
				var index = this.collection.indexOf( model ),
					$items = this.$el.children();

				if ( index > 0 ) {
					$items.eq( index ).insertBefore( $items.eq( index - 1 ) );
					this.updateOrder();
					wp.a11y.speak( this.control.params.labels.movedUp );
				}
			},

			updateOrder: function() {
				_.each( this.$el.children(), function( item, index ) {
					var id = $( item ).data( 'post-id' );
					this.collection.get( id ).set( 'sortableOrder', index );
				}, this );

				this.collection.sort();
			}
		}),

		ItemView: wp.Backbone.View.extend({
			tagName: 'li',
			className: 'wp-item',
			template: wp.template( 'wp-item' ),

			events: {
				'click .wp-item-delete': 'destroy',
				'click .move-item-up': 'moveUp',
				'click .move-item-down': 'moveDown'
			},

			initialize: function( options ) {
				this.control = options.control;
				this.parent = options.parent;
				this.listenTo( this.model, 'destroy', this.remove );
			},

			render: function() {
				var isFrontPage = this.model.get( 'id' ) == api( 'page_on_front' )(),
					canDelete = ! this.control.params.includeFrontPage || ! isFrontPage,
					data = _.extend( this.model.toJSON(), {
						labels: this.control.params.labels,
						includeFrontPage: this.control.params.includeFrontPage,
						showDeleteButton: canDelete
					});

				this.$el.html( this.template( data ) );
				this.$el.data( 'post-id', this.model.get( 'id' ) );

				if ( ! canDelete ) {
					this.$el.addClass( 'hide-delete' );
				}

				return this;
			},

			moveDown: function( e ) {
				e.preventDefault();
				this.parent.moveDown( this.model );
			},

			moveUp: function( e ) {
				e.preventDefault();
				this.parent.moveUp( this.model );
			},

			/**
			 * Destroy the view's model.
			 *
			 * Avoid syncing to the server by triggering an event instead of
			 * calling destroy() directly on the model.
			 */
			destroy: function() {
				this.model.trigger( 'destroy', this.model );
			},

			remove: function() {
				this.$el.remove();
			}
		})
	});

	/**
	 * Toggle the front page sections control based on front page settings.
	 */
	function toggleFrontPageSectionsControl() {
		var controlId = 'front_page_sections',
			showOnFront = api( 'show_on_front' )(),
			pageOnFront = api( 'page_on_front' )(),
			isVisible = 'page' === showOnFront && parseInt( pageOnFront ) > 0;

		if ( api.control.has( controlId ) ) {
			api.control( controlId ).container.toggle( isVisible );
		}
	}

	/**
	 * Create the collection for Drawers.
	 */
	api.drawer = new api.Values({ defaultConstructor: api.Drawer });

	/**
	 * Extends wp.customize.controlConstructor with control constructor for
	 * post_collection.
	 */
	$.extend( api.controlConstructor, {
		post_collection: api.PostCollectionControl
	});

	/**
	 * Bind events to toggle visibilty of the front page sections control.
	 */
	api.bind( 'ready', function() {
		api( 'show_on_front' ).bind( toggleFrontPageSectionsControl );
		api( 'page_on_front' ).bind( toggleFrontPageSectionsControl );
		api.section( 'static_front_page' ).expanded.bind( toggleFrontPageSectionsControl );
	});

})( window.wp, jQuery );
