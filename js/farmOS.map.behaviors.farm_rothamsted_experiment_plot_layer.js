(function () {
  farmOS.map.behaviours.farm_rothamsted_experiment_plot_layer = {
    attach: function (instance) {
      var url = new URL('/nfa-assets/geojson/' + instance.farmMapSettings.plan, window.location.origin + drupalSettings.path.baseUrl)
      var newLayer = instance.addLayer('geojson', {
        title: Drupal.t('Plots'),
        url,
        color: 'orange'
      })
      var source = newLayer.getSource()
      source.on('change', function () {
        instance.zoomToVectors()
      })
    }
  }
}())
