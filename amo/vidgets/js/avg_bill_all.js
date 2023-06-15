
Chart.register(ChartDataLabels);
const ctx = document.getElementById('densityChart');

const labels = ["<?=implode('","',array_keys($users))?>"]
const data = {
	labels: labels,
	datasets: [{
		axis: 'y',
		label: 'My First Dataset',
		data: [65, 59, 80, 81, 56, 55, 40],
		fill: false,
		backgroundColor: [
			'rgba(255, 99, 132, 0.2)',
			'rgba(255, 159, 64, 0.2)',
			'rgba(255, 205, 86, 0.2)',
			'rgba(75, 192, 192, 0.2)',
			'rgba(54, 162, 235, 0.2)',
			'rgba(153, 102, 255, 0.2)',
			'rgba(201, 203, 207, 0.2)'
		],
		borderColor: [
			'rgb(255, 99, 132)',
			'rgb(255, 159, 64)',
			'rgb(255, 205, 86)',
			'rgb(75, 192, 192)',
			'rgb(54, 162, 235)',
			'rgb(153, 102, 255)',
			'rgb(201, 203, 207)'
		],
		borderWidth: 1
	}]
};

const config = {
	type: 'bar',
	data,
	plugins: [ChartDataLabels],
	options: {
		plugins: {
			datalabels: {
				anchor: 'end',
				align: 'top',
				formatter: Math.round,
				font: {
					weight: 'bold',
					size: 16
				}
			}
		},
		indexAxis: 'y',
		tooltips: {
			enabled: false
		},
		hover: {
			animationDuration: 0
		},
		animation: {
			animation: {
				duration: 500,
				easing: "easeOutQuart",
				onComplete: function () {
					var ctx = this.chart.ctx;
					ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
					ctx.textAlign = 'center';
					ctx.textBaseline = 'bottom';
					this.data.datasets.forEach(function (dataset) {
						for (var i = 0; i < dataset.data.length; i++) {
							var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
								scale_max = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._yScale.maxHeight;
							ctx.fillStyle = '#444';
							var y_pos = model.y - 5;
							// Make sure data value does not get overflown and hidden
							// when the bar's value is too close to max value of scale
							// Note: The y value is reverse, it counts from top down
							if ((scale_max - model.y) / scale_max >= 0.93)
								y_pos = model.y + 20;
							ctx.fillText(dataset.data[i], model.x, y_pos);
						}
					});
				}
			}
		}
	}
};
new Chart(ctx, config);
