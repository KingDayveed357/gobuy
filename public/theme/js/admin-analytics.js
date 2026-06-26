/* goBuy admin analytics — Phoenix ECharts initializers.
   Chart styling follows frontend-template/modules/echarts patterns. */
(function () {
  'use strict';

  function getUtils() {
    return window.phoenix && window.phoenix.utils ? window.phoenix.utils : null;
  }

  function moneyKobo(kobo) {
    var n = Number(kobo || 0) / 100;
    return '₦' + n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  function onResize(chart) {
    window.addEventListener('resize', function () { chart.resize(); });
    var toggle = document.querySelector('.navbar-vertical-toggle');
    if (toggle) {
      toggle.addEventListener('navbar.vertical.toggle', function () { chart.resize(); });
    }
  }

  /* ── Revenue trend: smooth area/line with prior-period comparison ── */
  function initAreaLineChart(el, labels, values, previous) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var rgbaColor = utils.rgbaColor;
    var chart = window.echarts.init(el);
    chart.setOption({
      color: [getColor('primary'), getColor('info')],
      tooltip: {
        trigger: 'axis',
        backgroundColor: getColor('body-highlight-bg'),
        borderColor: getColor('border-color'),
        textStyle: { color: getColor('light-text-emphasis') },
        formatter: function (params) {
          var current = params[0];
          var prior = params[1];
          var html = '<div class="fs-9">' + current.axisValue + '</div>';
          html += '<div class="fs-9">' + current.marker + ' Revenue: ' + moneyKobo(current.value) + '</div>';
          if (prior) {
            html += '<div class="fs-9">' + prior.marker + ' Prior period: ' + moneyKobo(prior.value) + '</div>';
          }
          return html;
        },
      },
      legend: { top: 0, textStyle: { color: getColor('quaternary-color') } },
      grid: { left: 8, right: 8, bottom: 0, top: 40, containLabel: true },
      xAxis: {
        type: 'category',
        data: labels,
        boundaryGap: false,
        axisLine: { lineStyle: { color: getColor('tertiary-bg') } },
        axisLabel: { color: getColor('quaternary-color') },
      },
      yAxis: {
        type: 'value',
        splitLine: { lineStyle: { color: getColor('secondary-bg') } },
        axisLabel: {
          color: getColor('quaternary-color'),
          formatter: function (v) { return moneyKobo(v); },
        },
      },
      series: [
        {
          name: 'Revenue',
          type: 'line',
          smooth: true,
          showSymbol: false,
          data: values,
          areaStyle: { color: rgbaColor(getColor('primary'), 0.15) },
          lineStyle: { width: 3, color: getColor('primary') },
        },
        {
          name: 'Prior period',
          type: 'line',
          smooth: true,
          showSymbol: false,
          data: previous,
          lineStyle: { width: 2, type: 'dashed', color: getColor('info') },
        },
      ],
    });
    onResize(chart);
  }

  /* ── Customer retention: smooth area chart (replaces stacked bar) ── */
  function initRetentionAreaChart(el, labels, series) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var rgbaColor = utils.rgbaColor;
    var chart = window.echarts.init(el);
    chart.setOption({
      color: [getColor('primary'), getColor('info')],
      tooltip: {
        trigger: 'axis',
        backgroundColor: getColor('body-highlight-bg'),
        borderColor: getColor('border-color'),
        textStyle: { color: getColor('light-text-emphasis') },
      },
      legend: { top: 0, textStyle: { color: getColor('quaternary-color') } },
      grid: { left: 8, right: 8, bottom: 0, top: 40, containLabel: true },
      xAxis: {
        type: 'category',
        data: labels,
        boundaryGap: false,
        axisLine: { lineStyle: { color: getColor('tertiary-bg') } },
        axisLabel: { color: getColor('quaternary-color'), rotate: labels.length > 20 ? 30 : 0 },
      },
      yAxis: {
        type: 'value',
        minInterval: 1,
        splitLine: { lineStyle: { color: getColor('secondary-bg') } },
        axisLabel: { color: getColor('quaternary-color') },
      },
      series: series.map(function (s, i) {
        var colors = [getColor('primary'), getColor('info')];
        return {
          name: s.name,
          type: 'line',
          smooth: true,
          showSymbol: false,
          data: s.data,
          areaStyle: { color: rgbaColor(colors[i] || colors[0], 0.12) },
          lineStyle: { width: 2.5, color: colors[i] || colors[0] },
        };
      }),
    });
    onResize(chart);
  }

  /* ── Horizontal bar: top products ── */
  function initHorizontalBarChart(el, labels, values, colorKey) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var chart = window.echarts.init(el);
    chart.setOption({
      color: [getColor(colorKey || 'primary')],
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        backgroundColor: getColor('body-highlight-bg'),
        borderColor: getColor('border-color'),
        textStyle: { color: getColor('light-text-emphasis') },
        formatter: function (params) {
          var row = params[0];
          return row.name + ': ' + moneyKobo(row.value);
        },
      },
      grid: { left: 4, right: 16, bottom: 0, top: 4, containLabel: true },
      xAxis: {
        type: 'value',
        splitLine: { lineStyle: { color: getColor('secondary-bg') } },
        axisLabel: { color: getColor('quaternary-color'), formatter: function (v) { return moneyKobo(v); } },
      },
      yAxis: {
        type: 'category',
        data: labels.slice().reverse(),
        axisLabel: { color: getColor('quaternary-color') },
        axisLine: { show: false },
        axisTick: { show: false },
      },
      series: [{
        type: 'bar',
        data: values.slice().reverse(),
        barWidth: '60%',
        itemStyle: { borderRadius: [0, 4, 4, 0] },
      }],
    });
    onResize(chart);
  }

  /* ── Vertical bar: categories / failed payments ── */
  function initBarChart(el, labels, values) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var chart = window.echarts.init(el);
    chart.setOption({
      color: [getColor('primary')],
      tooltip: {
        trigger: 'axis',
        backgroundColor: getColor('body-highlight-bg'),
        borderColor: getColor('border-color'),
        textStyle: { color: getColor('light-text-emphasis') },
      },
      grid: { left: 8, right: 8, bottom: 0, top: 8, containLabel: true },
      xAxis: {
        type: 'category',
        data: labels,
        axisLabel: { color: getColor('quaternary-color'), rotate: labels.length > 6 ? 30 : 0 },
        axisLine: { lineStyle: { color: getColor('tertiary-bg') } },
      },
      yAxis: {
        type: 'value',
        splitLine: { lineStyle: { color: getColor('secondary-bg') } },
        axisLabel: { color: getColor('quaternary-color') },
      },
      series: [{ type: 'bar', data: values, barWidth: '55%', itemStyle: { borderRadius: [4, 4, 0, 0] } }],
    });
    onResize(chart);
  }

  /* ── Doughnut: order status / customer segment ── */
  function initDoughnutChart(el, labels, values) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var chart = window.echarts.init(el);
    chart.setOption({
      color: [getColor('primary'), getColor('info'), getColor('success'), getColor('warning'), getColor('danger'), getColor('secondary')],
      tooltip: {
        trigger: 'item',
        backgroundColor: getColor('body-highlight-bg'),
        borderColor: getColor('border-color'),
        textStyle: { color: getColor('light-text-emphasis') },
      },
      legend: {
        bottom: 0,
        textStyle: { color: getColor('quaternary-color') },
      },
      series: [{
        type: 'pie',
        radius: ['55%', '78%'],
        center: ['50%', '45%'],
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 4, borderColor: getColor('body-bg'), borderWidth: 2 },
        label: { show: false },
        data: labels.map(function (label, i) { return { name: label, value: values[i] }; }),
      }],
    });
    onResize(chart);
  }

  /* ── Forecast: actual + dashed projection ── */
  function initForecastChart(el, labels, actual, forecastStartIndex) {
    if (!el || !window.echarts) { return; }
    var utils = getUtils();
    if (!utils) { return; }
    var getColor = utils.getColor;
    var rgbaColor = utils.rgbaColor;
    var chart = window.echarts.init(el);
    var forecastData = actual.map(function (v, i) {
      return i >= forecastStartIndex ? v : null;
    });
    var historyData = actual.map(function (v, i) {
      return i < forecastStartIndex ? v : (i === forecastStartIndex - 1 ? v : null);
    });
    chart.setOption({
      tooltip: {
        trigger: 'axis',
        formatter: function (params) {
          var row = params.find(function (p) { return p.value != null; }) || params[0];
          return row.axisValue + ': ' + moneyKobo(row.value || 0);
        },
      },
      grid: { left: 8, right: 8, bottom: 0, top: 8, containLabel: true },
      xAxis: { type: 'category', data: labels, axisLabel: { color: getColor('quaternary-color') } },
      yAxis: {
        type: 'value',
        splitLine: { lineStyle: { color: getColor('secondary-bg') } },
        axisLabel: { color: getColor('quaternary-color'), formatter: function (v) { return moneyKobo(v); } },
      },
      series: [
        {
          name: 'Actual',
          type: 'line',
          smooth: true,
          showSymbol: false,
          data: historyData,
          lineStyle: { width: 3, color: getColor('primary') },
          areaStyle: { color: rgbaColor(getColor('primary'), 0.12) },
        },
        {
          name: 'Forecast',
          type: 'line',
          smooth: true,
          showSymbol: false,
          data: forecastData,
          lineStyle: { width: 2, type: 'dashed', color: getColor('warning') },
        },
      ],
    });
    onResize(chart);
  }

  /* ── Boot ── */
  document.addEventListener('DOMContentLoaded', function () {
    var data = window.adminAnalytics;
    if (!data) { return; }

    initAreaLineChart(
      document.getElementById('chartRevenueTrend'),
      data.revenue.labels,
      data.revenue.values,
      data.revenue.previous
    );

    initForecastChart(
      document.getElementById('chartRevenueForecast'),
      data.forecast.labels,
      data.forecast.values,
      data.forecast.splitIndex
    );

    initHorizontalBarChart(
      document.getElementById('chartTopProducts'),
      data.topProducts.labels,
      data.topProducts.values,
      'primary'
    );

    initBarChart(
      document.getElementById('chartTopCategories'),
      data.topCategories.labels,
      data.topCategories.values
    );

    initDoughnutChart(
      document.getElementById('chartOrderStatus'),
      data.orderStatus.labels,
      data.orderStatus.values
    );

    // Upgraded: smooth area chart instead of stacked bar
    initRetentionAreaChart(
      document.getElementById('chartCustomerRetention'),
      data.retention.labels,
      data.retention.series
    );

    // Customer segmentation donut (New / Returning / Guest)
    initDoughnutChart(
      document.getElementById('chartCustomerSegment'),
      data.customerSegment.labels,
      data.customerSegment.values
    );

    initBarChart(
      document.getElementById('chartFailedPayments'),
      data.failedPayments.labels,
      data.failedPayments.values
    );
  });
})();
