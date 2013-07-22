(function ($, window) {

/**
 * Provide the summary information for the stat method settings vertical tabs.
 */
Drupal.behaviors.sapiMethodSettingSummary = {
  attach: function () {
    // Make sure this behavior is processed only if drupalSetSummary is defined.
    if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
      return;
    }

    function findSummaries(context) {
      var vals = [];
      var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
      for (var i = 0, il = $checkboxes.length; i < il; i += 1) {
        vals.push($($checkboxes[i]).text());
      }
      var $selects = $(context).find('.form-type-select');
      for (var i = 0, il = $selects.length; i < il; i += 1) {
        vals.push($($selects[i]).find('label').text() + ': ' + $($selects[i]).find('option:selected').text());
      }

      if (context.id == 'edit-basic' && !vals.length) {
        vals.push(Drupal.t('Disabled'));
      }
      return vals.join(', ');
    }

    // Loop through each vertical tab detail.
    $('#stat-method-edit-form .vertical-tabs-panes > details').each(function(index, value) {
      $('#' + value.id).drupalSetSummary(findSummaries);
    });
  }
};


})(jQuery, window);
