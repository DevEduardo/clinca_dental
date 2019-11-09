<script>
import { Bar, mixins } from "vue-chartjs";

export default {
  extends: Bar,
  mixins: [mixins.reactiveData],
  props: ["data"],
  mounted: function() {
    this.renderLineChart();
  },
  methods: {
    renderLineChart: function() {
      this.renderChart(
        {
          labels: ["Total de pagos", "Total de gastos", "Total de comisiones"],
          datasets: [
            {
              fillColor: "rgba(172,194,132,0.4)",
              backgroundColor: [
                "#f87979",
                "#EA2027",
                "#006266",
                "#1B1464",
                "#5758BB",
                "#6F1E51"
              ],
              data: [
                this.data[0].totalPayments,
                this.data[0].totalExpenses,
                this.data[0].totalCommission
              ]
            }
          ]
        },
        {
          responsive: true,
          maintainAspectRatio: false,
          legend: { display: false }
        }
      );
    }
  },
  watch: {
    data: function() {
      this.renderLineChart();
    }
  }
};
</script>