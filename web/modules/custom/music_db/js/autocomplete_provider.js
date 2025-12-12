(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.musicDbAutocompleteProvider = {
    attach: function (context, settings) {
      // Find name or title input field - check multiple possible structures
      var nameInput = null;
      var possibleSelectors = [
        'input[name="name[0][value]"]',
        'input[name="name"]',
        'input[name="title[0][value]"]',
        'input[name="title"]',
        'input[name="title[widget][0][value]"]',
        'input[name="name[widget][0][value]"]'
      ];
      
      for (var s = 0; s < possibleSelectors.length; s++) {
        var found = context.querySelectorAll(possibleSelectors[s]);
        for (var f = 0; f < found.length; f++) {
          if (!found[f].hasAttribute('data-music-db-skip') && !found[f].hasAttribute('data-once-autocomplete-provider-name')) {
            found[f].setAttribute('data-once-autocomplete-provider-name', 'true');
            nameInput = found[f];
            break;
          }
        }
        if (nameInput) {
          break;
        }
      }
      var providerSelect = once('autocomplete-provider-select', 'select[name="autocomplete_provider[0][value]"], select[name="autocomplete_provider"]', context)[0];

      if (!providerSelect || !nameInput) {
        return;
      }

      function getFieldSelectors() {
        return {
          spotify: document.querySelector('input[name="spotify_id[0][value]"]') ||
                   document.querySelector('input[name="spotify_id"]'),
          discogs: document.querySelector('input[name="discogs_id[0][value]"]') ||
                   document.querySelector('input[name="discogs_id"]')
        };
      }

      function lookupOppositeId(artistName, currentProvider, targetField) {
        if (!artistName || !currentProvider || !targetField) {
          return;
        }
        if (targetField.value && targetField.value.trim() !== '') {
          return;
        }

        var lookupUrl = '/music_db/lookup/artist-id?name=' + encodeURIComponent(artistName) + '&provider=' + encodeURIComponent(currentProvider);

        jQuery.getJSON(lookupUrl)
          .done(function(data) {
            if (data.id) {
              targetField.value = data.id;
            }
          })
          .fail(function() {
          });
      }

      function updateAutocomplete() {
        var provider = providerSelect.value;
        var routePath = null;
        var description = 'Enter the name.';
        var formEl = nameInput.closest('form');
        var autocompleteType = formEl ? formEl.getAttribute('data-music-db-autocomplete-type') : 'artist';

        if (provider === 'spotify') {
          if (autocompleteType === 'album') {
            routePath = '/music_db/autocomplete/spotify/album';
            description = 'Start typing to search Spotify and pick the correct album.';
          }
          else if (autocompleteType === 'song') {
            routePath = '/music_db/autocomplete/spotify/song';
            description = 'Start typing to search Spotify and pick the correct song.';
          }
          else {
            routePath = '/music_db/autocomplete/spotify/artist';
            description = 'Start typing to search Spotify and pick the correct artist.';
          }
        }
        else if (provider === 'discogs') {
          if (autocompleteType === 'album') {
            routePath = '/music_db/autocomplete/discogs/album';
            description = 'Start typing to search Discogs and pick the correct album.';
          }
          else if (autocompleteType === 'song') {
            routePath = '/music_db/autocomplete/discogs/song';
            description = 'Start typing to search Discogs and pick the correct song.';
          }
          else {
            routePath = '/music_db/autocomplete/discogs/artist';
            description = 'Start typing to search Discogs and pick the correct artist.';
          }
        }
        var autocompleteOnce = 'autocomplete';
        var onceAttr = 'data-once-' + autocompleteOnce;
        nameInput.removeAttribute(onceAttr);
        nameInput.classList.remove('form-autocomplete');
        nameInput.removeAttribute('data-drupal-autocomplete-path');

        var autocompleteWrapper = nameInput.parentElement.querySelector('.ui-autocomplete');
        if (autocompleteWrapper) {
          autocompleteWrapper.remove();
        }

        if (routePath) {
          if (typeof jQuery !== 'undefined' && jQuery(nameInput).autocomplete) {
            try {
              var instance = jQuery(nameInput).autocomplete('instance');
              if (instance) {
                jQuery(nameInput).autocomplete('destroy');
              }
            }
            catch (e) {
            }
          }

          nameInput.setAttribute('data-drupal-autocomplete-path', routePath);
          nameInput.setAttribute('autocomplete', 'off');
          nameInput.classList.add('form-autocomplete');

          nameInput.removeAttribute('data-once-autocomplete');

          if (typeof jQuery !== 'undefined' && typeof jQuery.ui !== 'undefined' && jQuery.ui.autocomplete) {
            nameInput.setAttribute('data-music-db-skip', 'true');

            var autocompletePath = routePath;
            jQuery(nameInput).autocomplete({
              source: function(request, response) {
                var path = autocompletePath + '?q=' + encodeURIComponent(request.term);
                jQuery.getJSON(path).done(function(data) {
                  response(data);
                }).fail(function() {
                  response([]);
                });
              },
              minLength: 2,
              select: function(event, ui) {
                nameInput.value = ui.item.value;
                var artistName = ui.item.value;
                var fields = getFieldSelectors();

                if (provider === 'spotify' && fields.spotify && ui.item.spotify_id) {
                  fields.spotify.value = ui.item.spotify_id;

                  if (fields.discogs && artistName) {
                    lookupOppositeId(artistName, 'spotify', fields.discogs);
                  }
                }
                else if (provider === 'discogs' && fields.discogs && ui.item.discogs_id) {
                  fields.discogs.value = ui.item.discogs_id;

                  if (fields.spotify && artistName) {
                    lookupOppositeId(artistName, 'discogs', fields.spotify);
                  }
                }

                return false;
              }
            });

            setTimeout(function() {
              nameInput.removeAttribute('data-music-db-skip');
            }, 100);
          }
        }

        var wrapper = nameInput.closest('.js-form-item-name, .form-item-name, .form-item, .js-form-item-title, .form-item-title');
        if (wrapper) {
          var descriptionElement = wrapper.querySelector('.description');
          if (descriptionElement) {
            descriptionElement.textContent = description;
          }
          else {
            var desc = document.createElement('div');
            desc.className = 'description';
            desc.textContent = description;
            wrapper.appendChild(desc);
          }
        }
      }

      providerSelect.addEventListener('change', updateAutocomplete);

      var nameInputTimeout = null;
      nameInput.addEventListener('blur', function() {
        var enteredName = nameInput.value.trim();
        if (enteredName.length < 2) {
          return;
        }

        if (nameInputTimeout) {
          clearTimeout(nameInputTimeout);
        }

        nameInputTimeout = setTimeout(function() {
          var currentProvider = providerSelect.value;
          var fields = getFieldSelectors();

          if (currentProvider === 'spotify' && fields.spotify && !fields.spotify.value) {
            lookupOppositeId(enteredName, 'discogs', fields.spotify);
            if (fields.discogs && !fields.discogs.value) {
              lookupOppositeId(enteredName, 'spotify', fields.discogs);
            }
          }
          else if (currentProvider === 'discogs' && fields.discogs && !fields.discogs.value) {
            lookupOppositeId(enteredName, 'spotify', fields.discogs);
            if (fields.spotify && !fields.spotify.value) {
              lookupOppositeId(enteredName, 'discogs', fields.spotify);
            }
          }
        }, 500);
      });

      setTimeout(updateAutocomplete, 100);
    }
  };

})(Drupal, once);
