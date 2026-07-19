function renderSkillMapCharts() {
  if (typeof Chart === 'undefined') {
    return;
  }

  const defaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: {
          usePointStyle: true,
          boxWidth: 10,
        },
      },
    },
  };

  const lineCanvas = document.getElementById('matchScoreChart');
  if (lineCanvas) {
    new Chart(lineCanvas, {
      type: 'line',
      data: {
        labels: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [{
          label: 'Match Score',
          data: [58, 62, 68, 73, 77, 82],
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.12)',
          tension: 0.35,
          fill: true,
        }],
      },
      options: {
        ...defaults,
        scales: {
          y: { beginAtZero: true, max: 100 },
        },
      },
    });
  }

  const progressLine = document.getElementById('progressTrendChart');
  if (progressLine) {
    new Chart(progressLine, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [{
          label: 'Trend',
          data: [49, 54, 58, 64, 69, 74, 82],
          borderColor: '#16a34a',
          backgroundColor: 'rgba(22, 163, 74, 0.1)',
          fill: true,
          tension: 0.35,
        }],
      },
      options: {
        ...defaults,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } },
      },
    });
  }

  const radarCanvas = document.getElementById('skillRadarChart');
  if (radarCanvas) {
    new Chart(radarCanvas, {
      type: 'radar',
      data: {
        labels: ['Leadership', 'Technical', 'Interpersonal', 'Academic', 'Organisational'],
        datasets: [
          {
            label: 'You',
            data: [72, 84, 78, 66, 70],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.14)',
            pointBackgroundColor: '#2563eb',
          },
          {
            label: 'Target',
            data: [80, 92, 85, 74, 76],
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124, 58, 237, 0.12)',
            pointBackgroundColor: '#7c3aed',
          },
        ],
      },
      options: {
        ...defaults,
        scales: { r: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } } },
      },
    });
  }

  const analyticsDoughnut = document.getElementById('categoryPopularityChart');
  if (analyticsDoughnut) {
    new Chart(analyticsDoughnut, {
      type: 'doughnut',
      data: {
        labels: ['Technical', 'Leadership', 'Interpersonal', 'Academic', 'Organisational'],
        datasets: [{
          data: [42, 16, 14, 18, 10],
          backgroundColor: ['#2563eb', '#7c3aed', '#10b981', '#f59e0b', '#64748b'],
          borderWidth: 0,
        }],
      },
      options: {
        ...defaults,
        cutout: '68%',
      },
    });
  }

  const barCanvas = document.getElementById('programmeReadinessChart');
  if (barCanvas) {
    new Chart(barCanvas, {
      type: 'bar',
      data: {
        labels: ['Leadership', 'Technical', 'Interpersonal', 'Academic', 'Organisational'],
        datasets: [
          { label: 'Information Systems', data: [72, 84, 78, 66, 70], backgroundColor: '#2563eb' },
          { label: 'Software Engineering', data: [68, 88, 70, 64, 73], backgroundColor: '#7c3aed' },
          { label: 'Computer Science', data: [66, 86, 74, 62, 68], backgroundColor: '#14b8a6' },
        ],
      },
      options: {
        ...defaults,
        scales: { y: { beginAtZero: true, max: 100 } },
      },
    });
  }

  const skillBreakdownDonut = document.getElementById('skillBreakdownChart');
  if (skillBreakdownDonut) {
    new Chart(skillBreakdownDonut, {
      type: 'doughnut',
      data: {
        labels: ['Have', 'Partial', 'Missing'],
        datasets: [{
          data: [8, 4, 2],
          backgroundColor: ['#16a34a', '#eab308', '#dc2626'],
          borderWidth: 0,
        }],
      },
      options: {
        ...defaults,
        cutout: '72%',
      },
    });
  }
}

document.addEventListener('DOMContentLoaded', renderSkillMapCharts);
