import React from 'react';
import Chart from 'react-apexcharts';

export default function ReportChart({ title, categories = [], series = [], type = 'bar', height = 320, stacked = false, columnWidth = '50%' }) {
  const options = {
    chart: {
      type,
      toolbar: { show: true },
      animations: { easing: 'easeinout', speed: 400 },
    },
    legend: { position: 'top' },
    dataLabels: { enabled: false },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: columnWidth,
        borderRadius: 6,
        rangeBarOverlap: stacked,
        rangeBarGroupRows: stacked,
        distributed: false,
        stacked,
      },
    },
    stroke: { width: type === 'line' ? 2 : 0, curve: 'smooth' },
    colors: ['#22c55e', '#ef4444', '#f59e0b', '#3b82f6', '#a78bfa'], // verde, vermelho, amber, azul, roxo
    xaxis: { categories, labels: { rotate: -45 } },
    yaxis: { title: { text: 'Quantidade' } },
    theme: { mode: 'light' },
    tooltip: { shared: true, intersect: false },
    grid: { strokeDashArray: 3 },
  };

  return (
    <div className="bg-white shadow-sm rounded-lg border border-gray-200">
      <div className="px-4 py-3 border-b border-gray-100">
        <h3 className="text-sm font-medium text-gray-700">{title}</h3>
      </div>
      <div className="p-4">
        <Chart options={options} series={series} type={type} height={height} />
      </div>
    </div>
  );
}