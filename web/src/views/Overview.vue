<template>
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-4">Overview</h1>
    <div class="chart-container">
      <canvas id="fundsChart"></canvas>
    </div>
  </div>
</template>

<script>
import { onMounted } from 'vue';
import Chart from 'chart.js/auto';

export default {
  name: 'Overview',
  setup() {
    const fetchData = async () => {
      // Fetch data from the backend
      const response = await fetch('/api/funds');
      const data = await response.json();
      return data;
    };

    const createChart = (data) => {
      const ctx = document.getElementById('fundsChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Funds Over Time',
              data: data.values,
              borderColor: 'rgba(75, 192, 192, 1)',
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            x: {
              type: 'time',
              time: {
                unit: 'month',
              },
            },
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    };

    onMounted(async () => {
      const data = await fetchData();
      createChart(data);
    });
  },
};
</script>

<style scoped>
.container {
  max-width: 800px;
  margin: 0 auto;
}

.chart-container {
  position: relative;
  height: 400px;
}
</style>
