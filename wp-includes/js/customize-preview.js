(function( exports, $ ) {
	var api = wp.customize,
		settings = _frontPageSectionsSettings;

	api.selectiveRefresh.FrontPageSectionsPartial = api.selectiveRefresh.Partial.extend({
		ready: function() {
			var partial = this;

			this.postIds = this.getPostIds();

			// Initialize a partial for each post.
			_.each( this.postIds, function( postId ) {
				partial.createSectionPartial( postId );
			});

			api.selectiveRefresh.Partial.prototype.ready.call( this, arguments );
		},

		getPostIds: function() {
			var value = api( this.settings().shift() )();

			if ( _.isString( value ) ) {
				value = value.split( ',' );
			}

			return _.map( value, function( id ) {
				return parseInt( id, 10 );
			});
		},

		refresh: function() {
			var partial = this,
				deferred = $.Deferred(),
				postIds = this.getPostIds(),
				addedPosts = _.difference( postIds, this.postIds ),
				removedPosts = _.difference( this.postIds, postIds );

			deferred.fail(function() {
				partial.fallback();
			});

			if ( 0 === partial.placements().length ) {
				deferred.reject();
			}

			if ( addedPosts.length > 0 ) {
				// @todo Reject for now.
				// Need to come up with a way to inject a container with the appropriate selector.
				// Need to load new scripts and styles when a new section requires them (MediaElement, Jetpack Tiled Gallery, etc).
				deferred.reject();

				 _.each( addedPosts, function( postId ) {
					partial.addSectionPartial( postId );
				});

				partial.reflowSections();
			}

			if ( removedPosts.length > 0 ) {
				_.each( removedPosts, function( postId ) {
					partial.removeSectionPartial( postId );
				});
			}

			if ( addedPosts.length < 1 && removedPosts.length < 1 ) {
				this.reflowSections();
			}

			this.postIds = postIds;
			deferred.resolve();

			return deferred.promise();
		},

		/**
		 * Creates a new partial or retrieves it if it already exists in the
		 * global partial collection.
		 */
		createSectionPartial: function( postId ) {
			var sectionPartial,
				sectionPartialId = 'front_page_section[' + postId + ']';

			// @todo This needs to include the anchor.
			sectionPartial = api.selectiveRefresh.partial( sectionPartialId );
			if ( ! sectionPartial ) {
				// @todo Insert a container if one doesn't exist (or create a new partial class for individual sections).

				sectionPartial = new api.selectiveRefresh.Partial( sectionPartialId, {
					params: {
						selector: settings.section_selector.replace( '%d', postId ),
						fallbackRefresh: false
					}
				});

				api.selectiveRefresh.partial.add( sectionPartial.id, sectionPartial );
			}

			return sectionPartial;
		},

		addSectionPartial: function( postId ) {
			var partial = this,
				sectionPartialId = 'front_page_section[' + postId + ']',
				sectionPartial = partial.createSectionPartial( postId );

			sectionPartial
				.refresh()
				.done(function( placements ) {
					_.each( placements, function( placement ) {
						partial.placements()[0].container.append( placement.addedContent );
					});
				});

			return sectionPartial;
		},

		removeSectionPartial: function( postId ) {
			var partial = this,
				sectionPartialId = 'front_page_section[' + postId + ']';

			_.each( api.selectiveRefresh.partial( sectionPartialId ).placements(), function( placement ) {
				// Remove from the DOM.
				placement.container.fadeOut( 'fast', function() {
					placement.container.remove();
				})
			});

			return this;
		},

		reflowSections: function() {
			var partial = this,
				$container = this.placements()[0].container;

			_.each( this.getPostIds(), function( postId ) {
				var sectionPartial = api.selectiveRefresh.partial( 'front_page_section[' + postId + ']' );

				_.each( sectionPartial.placements(), function( placement ) {
					$container.append( placement.container[0] );
				});
			});
		}
	});

	$.extend( api.selectiveRefresh.partialConstructor, {
		front_page_sections: api.selectiveRefresh.FrontPageSectionsPartial
	});

})( window, jQuery );
