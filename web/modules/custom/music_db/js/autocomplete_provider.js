(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.musicDbAutocompleteProvider = {
    attach: function (context, settings) {
      var inputs = once('autocomplete-provider-name', 'input[name="name[0][value]"], input[name="name"]', context);
      var nameInput = null;
      for (var i = 0; i < inputs.length; i++) {
        if (!inputs[i].hasAttribute('data-music-db-skip')) {
          nameInput = inputs[i];
          break;
        }
      }
      var providerSelect = once('autocomplete-provider-select', 'select[name="autocomplete_provider[0][value]"], select[name="autocomplete_provider"]', context)[0];

      if (!providerSelect || !nameInput) {
        return;
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
        var description = 'Enter the artist name.';

        if (provider === 'spotify') {
          routePath = '/music_db/autocomplete/spotify/artist';
          description = 'Start typing to search Spotify and pick the correct artist.';
        }
        else if (provider === 'discogs') {
          routePath = '/music_db/autocomplete/discogs/artist';
          description = 'Start typing to search Discogs and pick the correct artist.';
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

                var spotifyField = document.querySelector('input[name="spotify_id[0][value]"]') ||
                                   document.querySelector('input[name="spotify_id"]');
                var discogsField = document.querySelector('input[name="discogs_id[0][value]"]') ||
                                   document.querySelector('input[name="discogs_id"]');

                if (provider === 'spotify' && spotifyField && ui.item.spotify_id) {
                  spotifyField.value = ui.item.spotify_id;

                  if (discogsField && artistName) {
                    lookupOppositeId(artistName, 'spotify', discogsField);
                  }
                }
                else if (provider === 'discogs' && discogsField && ui.item.discogs_id) {
                  discogsField.value = ui.item.discogs_id;

                  if (spotifyField && artistName) {
                    lookupOppositeId(artistName, 'discogs', spotifyField);
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

        var wrapper = nameInput.closest('.js-form-item-name, .form-item-name, .form-item');
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
          var spotifyField = document.querySelector('input[name="spotify_id[0][value]"]') ||
                             document.querySelector('input[name="spotify_id"]');
          var discogsField = document.querySelector('input[name="discogs_id[0][value]"]') ||
                             document.querySelector('input[name="discogs_id"]');

          if (currentProvider === 'spotify' && spotifyField && !spotifyField.value) {
            lookupOppositeId(enteredName, 'discogs', spotifyField);
            if (discogsField && !discogsField.value) {
              lookupOppositeId(enteredName, 'spotify', discogsField);
            }
          }
          else if (currentProvider === 'discogs' && discogsField && !discogsField.value) {
            lookupOppositeId(enteredName, 'spotify', discogsField);
            if (spotifyField && !spotifyField.value) {
              lookupOppositeId(enteredName, 'discogs', spotifyField);
            }
          }
        }, 500);
      });

      setTimeout(updateAutocomplete, 100);
    }
  };

})(Drupal, once);

