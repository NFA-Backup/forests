(function () {
    farmOS.map.behaviors.farm_nfa_gfw_layers = {
      attach: async function (instance) {
        // Add layers for fire and deforestation alerts in the GFW plan tab
        farmNfaPlotGfwApiMap(instance,'fire', 'https://data-api.globalforestwatch.org/dataset/nasa_viirs_fire_alerts/v20220726/query/json');
        farmNfaPlotGfwApiMap(instance,'deforestation', 'https://data-api.globalforestwatch.org/dataset/gfw_integrated_alerts/v20230215/query/json');
      }
    }
}())

async function farmNfaPlotGfwApiMap(instance, mapType, gfwApiUrl) {
  let planId = instance.farmMapSettings.plan;
  const pageOrigin = 'https://' + instance.farmMapSettings.host;
  let cfrPlanUrl = `${pageOrigin}/nfa-assets/geojson/${planId}`;
  try {
    let cfr = await (await fetch(cfrPlanUrl)).json();
    let geoJson = {
      "type": "FeatureCollection",
      "features": []
    };
    let gfwLocationData = [];
    for (let i = 0; i < cfr.features.length; i++) {
      let cfrGeometry = cfr.features[i].geometry;
      let gfwApiBody = {
        "geometry": {
          "type": "Polygon",
          "coordinates": []
        },
        "sql": `SELECT latitude,longitude FROM results WHERE ${mapType == "fire" ? "iso='UGA' AND alert__date >='2023-02-14'" : "gfw_integrated_alerts__date >='2023-01-15'"}`
      };
      if (cfrGeometry && cfrGeometry.coordinates) {
        gfwApiBody.geometry.coordinates.push(cfrGeometry.coordinates[0]);
        gfwLocationData.push(
          fetch(gfwApiUrl,{
            method: 'POST',
            body: JSON.stringify(gfwApiBody),
            headers: {
              'Content-Type': 'application/json'
            }
          })
        );
      }
    }
    let locationData = await Promise.all(gfwLocationData);
    locationData = await Promise.all(locationData.map((location) => location.json()));
    locationData.forEach((location) => {
      let locations = location && location.data;
      if (locations) {
        locations.forEach((location) => {
          let latLongArray = [location.longitude, location.latitude];
          let geoJsonFeature = {
            "type": "Feature",
            "properties": {},
            "geometry": {
              "coordinates": latLongArray,
              "type": "Point"
            }
          };
          geoJson.features.push(geoJsonFeature);
        })
      }
    });
    instance.addLayer('geojson', {
      title: `${mapType == "fire" ? "Fire": "Deforestation"} Alerts`,
      geojson : geoJson,
      color: `${mapType == "fire" ? "red": "green"}`
    });
    if (mapType !== "fire") {
      let allLayersControllers = document.querySelectorAll(".layer-switcher input");
      allLayersControllers.forEach((layerController) => {
        if (layerController.nextSibling.innerText !== "Fire Alerts") {
          layerController.click();
        }
      });
    }
  } catch(err) {}
}
