<?php
/**
 * @copyright (C) 2022 SoftPlaza.NET
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package dashboard
 * @author SoftPlaza.NET
 */

defined( 'ABSPATH' ) OR die();
?>
<!-- Dashboard Section -->
<h1 class="h4 my-2">Analytics Dashboard</h1>
<main id="main" class="dashboard">

    <section id="spn_counters">
        <div class="row">

            <div class="col-xxl-4 col-xl-12">
                <div class="card info-card users-card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                            <h5 class="text-muted">Total users</h5>
                            <h3>155</h3>
                        </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-xxl-4 col-xl-12">
                <div class="card info-card posts-card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-card-text"></i>
                        </div>
                        <div class="ps-3">
                            <h5 class="text-muted">Total posts</h5>
                            <h3>527</h3>
                        </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-xxl-4 col-xl-12">
                <div class="card info-card comments-card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="ps-3">
                            <h5 class="text-muted">Total comments</h5>
                            <h3>527</h3>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>


    <section id="spn_charts">
        <div class="row">

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                    <h5 class="card-title">Users by Group</h5>

                    <!-- Pie Chart -->
                    <div id="pieChart"></div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    new ApexCharts(document.querySelector("#pieChart"), {
        series: [44, 55, 13, 43, 22],
        chart: {
        height: 350,
        type: 'pie',
        toolbar: {
            show: true
        }
        },
        labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E']
    }).render();
});
    
</script>
<!-- End Pie Chart -->

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                    <h5 class="card-title">Donut Chart</h5>

                    <!-- Donut Chart -->
                    <div id="donutChart"></div>

                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                        new ApexCharts(document.querySelector("#donutChart"), {
                            series: [44, 55, 13, 43, 22],
                            chart: {
                            height: 350,
                            type: 'donut',
                            toolbar: {
                                show: true
                            }
                            },
                            labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E'],
                        }).render();
                        });
                    </script>
                    <!-- End Donut Chart -->

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                    <h5 class="card-title">Pie Chart</h5>

                    <!-- Pie Chart -->
                    <div id="pieChart2"></div>

                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                        new ApexCharts(document.querySelector("#pieChart2"), {
                            series: [44, 55, 13, 43, 22],
                            chart: {
                            height: 350,
                            type: 'pie',
                            toolbar: {
                                show: true
                            }
                            },
                            labels: ['Team A', 'Team B', 'Team C', 'Team D', 'Team E']
                        }).render();
                        });
                    </script>
                    <!-- End Pie Chart -->

                    </div>
                </div>
            </div>


        </div>
    </section>


    <section id="spn_tables">
        <div class="row">

            <div class="col-xxl-4 col-xl-12">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>New Users</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">

                    </tbody>
                </table>
            </div>

            <div class="col-xxl-4 col-xl-12">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Last posts</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">

                    </tbody>
                </table>
            </div>

            <div class="col-xxl-4 col-xl-12">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Last comments</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">

                    </tbody>
                </table>
            </div>

        </div>
    </section>

 


</div>






