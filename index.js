// Dashboard Data Management
class DashboardManager {
    constructor() {
        this.data = {
            campaigns: [],
            events: [],
            stats: {
                activeCampaigns: 0,
                upcomingEvents: 0,
                contentItems: 0,
                surveyResponses: 0
            },
            performanceData: {
                campaignPerformance: [],
                audienceEngagement: [25, 35, 20, 20], // This will be updated from API
                contentViews: [],
                contentTypes: []
            }
        };
        this.refreshInterval = null;
        this.charts = {};
        this.lastUpdate = new Date();
        this.apiBaseUrl = 'backend/api/';
        this.isInitialized = false; // Track initialization state
    }

    // Initialize the dashboard
    async init() {
        // Prevent multiple initializations
        if (this.isInitialized) {
            console.log('Dashboard already initialized, skipping...');
            return;
        }
        
        try {
            // Load data from API first
            await this.loadDashboardData();
            
            // Then initialize UI components
            this.initializeCharts();
            this.updateStats();
            this.renderCampaignTable();
            this.renderEventsList();
            this.setupEventListeners();
            this.startAutoRefresh();
            
            this.isInitialized = true;
            console.log('Dashboard initialized successfully');
        } catch (error) {
            console.error('Failed to initialize dashboard:', error);
            // Initialize with fallback data if API fails
            this.initializeFallbackData();
            this.initializeCharts();
            this.updateStats();
            this.renderCampaignTable();
            this.renderEventsList();
            this.setupEventListeners();
            
            this.isInitialized = true;
            console.log('Dashboard initialized with fallback data');
        }
    }

    // Initialize all charts
    initializeCharts() {
        this.initCampaignPerformanceChart();
        this.initAudienceEngagementChart();
        this.initContentPerformanceChart();
        this.initContentTypeChart();
    }

    // Initialize Campaign Performance Chart with interactivity
    initCampaignPerformanceChart() {
        const ctx = document.getElementById('campaignPerformanceChart')?.getContext('2d');
        if (!ctx) return;

        this.charts.campaignPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Road Safety', 'Cyber Security', 'Fire Prevention', 'Disaster Preparedness'],
                datasets: [{
                    label: 'Engagement Rate (%)',
                    data: this.data.performanceData.campaignPerformance,
                    backgroundColor: [
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderColor: [
                        'rgb(37, 99, 235)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(16, 185, 129)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        this.showCampaignDetails(index);
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: (context) => {
                                return 'Click for details';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Engagement Rate (%)'
                        }
                    }
                }
            }
        });
    }

    // Initialize Audience Engagement Chart
    initAudienceEngagementChart() {
        const ctx = document.getElementById('audienceEngagementChart')?.getContext('2d');
        if (!ctx) return;

        this.charts.audienceEngagement = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Participants', 'Occasional Engagers', 'Minimal Engagement', 'Not Engaged'],
                datasets: [{
                    data: this.data.performanceData.audienceEngagement,
                    backgroundColor: [
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ],
                    borderColor: [
                        'rgb(37, 99, 235)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        this.showAudienceSegmentDetails(index);
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Initialize Content Performance Chart
    initContentPerformanceChart() {
        const ctx = document.getElementById('contentPerformanceChart')?.getContext('2d');
        if (!ctx) return;

        this.charts.contentPerformance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Content Views',
                    data: this.data.performanceData.contentViews,
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: (context) => {
                                const growth = context.dataIndex > 0 ? 
                                    ((context.raw - this.data.performanceData.contentViews[context.dataIndex - 1]) / this.data.performanceData.contentViews[context.dataIndex - 1] * 100).toFixed(1) : 0;
                                return growth !== 0 ? `Growth: ${growth}%` : '';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Views'
                        }
                    }
                }
            }
        });
    }

    // Initialize Content Type Chart
    initContentTypeChart() {
        const ctx = document.getElementById('contentTypeChart')?.getContext('2d');
        if (!ctx) return;

        this.charts.contentType = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Videos', 'Posters', 'Guidelines', 'Social Media', 'Other'],
                datasets: [{
                    data: this.data.performanceData.contentTypes,
                    backgroundColor: [
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(100, 116, 139, 0.7)'
                    ],
                    borderColor: [
                        'rgb(37, 99, 235)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(100, 116, 139)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Update dashboard statistics
    updateStats() {
        const statElements = {
            activeCampaigns: document.querySelector('.stat-card:nth-child(1) .stat-value'),
            upcomingEvents: document.querySelector('.stat-card:nth-child(2) .stat-value'),
            contentItems: document.querySelector('.stat-card:nth-child(3) .stat-value'),
            surveyResponses: document.querySelector('.stat-card:nth-child(4) .stat-value')
        };

        Object.keys(statElements).forEach(key => {
            if (statElements[key]) {
                // Use simple text update instead of animation to prevent blinking
                statElements[key].textContent = this.data.stats[key].toLocaleString();
            }
        });

        // Update last refresh time
        this.updateLastRefreshTime();
    }

    // Animate counter numbers
    animateCounter(element, targetValue) {
        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000;
        const startTime = performance.now();

        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
            element.textContent = currentValue.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        };

        requestAnimationFrame(updateCounter);
    }

    // Render campaigns table with interactive features
    renderCampaignTable() {
        const tableBody = document.querySelector('.campaigns-table tbody');
        if (!tableBody) return;

        tableBody.innerHTML = this.data.campaigns.map(campaign => {
            const statusClass = campaign.status === 'active' ? 'active' : 
                               campaign.status === 'planning' ? 'planning' : 
                               campaign.status === 'completed' ? 'completed' : 'draft';
            
            const progress = campaign.progress || 0;
            const progressColor = progress >= 75 ? 'success' : 
                                 progress >= 50 ? 'warning' : 'info';
            
            const engagement = campaign.engagement_rate || campaign.engagement || 0;

            return `
                <tr data-campaign-id="${campaign.id}" class="campaign-row">
                    <td><strong>${escapeHtml(campaign.name)}</strong></td>
                    <td><span class="status-badge ${statusClass}">${campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1)}</span></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill ${progressColor}" style="width: ${progress}%"></div>
                        </div>
                        <small class="progress-text">${progress}%</small>
                    </td>
                    <td><strong>${engagement}%</strong></td>
                    <td>
                        <button class="btn-icon blue" onclick="dashboard.viewCampaignDetails(${campaign.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon green" onclick="dashboard.exportCampaignData(${campaign.id})" title="Export Data">
                            <i class="fas fa-download"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Add hover effects for rows
        tableBody.querySelectorAll('.campaign-row').forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = 'rgba(37, 99, 235, 0.05)';
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
            });
        });
    }

    // Render events list
    renderEventsList() {
        const eventsList = document.querySelector('.dashboard-card .events-list');
        if (!eventsList) return;

        eventsList.innerHTML = this.data.events.map(event => {
            const eventDate = new Date(event.event_date || event.date);
            const month = eventDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
            const day = eventDate.getDate();
            
            const colors = ['red', 'blue', 'green'];
            const color = colors[event.id % colors.length];
            
            const registeredCount = event.registered_count || event.registered || 0;
            const campaignName = event.campaign_name || event.campaign || 'Unknown Campaign';

            return `
                <div class="event-item" data-event-id="${event.id}">
                    <div class="event-date ${color}">
                        <div class="event-day">${day}</div>
                        <div class="event-month">${month}</div>
                    </div>
                    <div class="event-details">
                        <h4 class="event-title">${escapeHtml(event.title)}</h4>
                        <p class="event-info">
                            <i class="fas fa-users"></i> ${registeredCount} registered
                        </p>
                        <p class="event-info">
                            <i class="fas fa-tag"></i> ${escapeHtml(campaignName)}
                        </p>
                    </div>
                    <div class="event-actions">
                        <button class="btn-icon blue" onclick="dashboard.viewEventDetails(${event.id})" title="View Details">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Setup event listeners for dashboard controls
    setupEventListeners() {
        // Refresh button
        this.addRefreshButton();
        
        // Export buttons
        this.addExportButtons();
        
        // Time range selectors
        this.addTimeRangeSelectors();
        
        // Real-time toggle
        this.addRealTimeToggle();
    }

    // Add refresh button to dashboard
    addRefreshButton() {
        const controlsContainer = document.querySelector('.dashboard-controls');
        if (!controlsContainer || controlsContainer.querySelector('.refresh-btn')) return;

        const refreshBtn = document.createElement('button');
        refreshBtn.className = 'btn-secondary refresh-btn';
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
        refreshBtn.onclick = () => this.refreshDashboard();
        
        controlsContainer.appendChild(refreshBtn);
    }

    // Add export functionality
    addExportButtons() {
        const controlsContainer = document.querySelector('.dashboard-controls');
        if (!controlsContainer || controlsContainer.querySelector('.export-btn')) return;

        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn-primary export-btn';
        exportBtn.innerHTML = '<i class="fas fa-file-export"></i> Export Dashboard';
        exportBtn.onclick = () => this.exportDashboard();
        
        controlsContainer.appendChild(exportBtn);
    }

    // Add time range selectors
    addTimeRangeSelectors() {
        // Find chart containers and add time range selectors
        const chartContainers = document.querySelectorAll('.dashboard-card .chart-wrapper');
        chartContainers.forEach((container, index) => {
            const selector = document.createElement('select');
            selector.className = 'time-range-selector';
            selector.innerHTML = `
                <option value="7d">Last 7 days</option>
                <option value="30d" selected>Last 30 days</option>
                <option value="90d">Last 90 days</option>
                <option value="1y">Last year</option>
            `;
            
            selector.onchange = (e) => this.updateChartTimeRange(index, e.target.value);
            
            // Insert before the chart
            container.parentNode.insertBefore(selector, container);
        });
    }

    // Add real-time toggle
    addRealTimeToggle() {
        const controlsContainer = document.querySelector('.dashboard-controls');
        if (!controlsContainer || controlsContainer.querySelector('.real-time-toggle')) return;

        const toggleContainer = document.createElement('div');
        toggleContainer.className = 'real-time-toggle';
        toggleContainer.innerHTML = `
            <label class="toggle-switch">
                <input type="checkbox" id="realTimeToggle" checked>
                <span class="toggle-slider"></span>
                <span class="toggle-label">Real-time updates</span>
            </label>
        `;
        
        const toggle = toggleContainer.querySelector('#realTimeToggle');
        toggle.onchange = (e) => {
            if (e.target.checked) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        };
        
        controlsContainer.appendChild(toggleContainer);
    }

    // Start auto-refresh
    startAutoRefresh() {
        this.stopAutoRefresh(); // Clear any existing interval
        // Refresh every 2 minutes instead of 30 seconds to reduce blinking
        this.refreshInterval = setInterval(() => {
            this.simulateDataUpdate();
            this.refreshDashboard();
        }, 120000); // Refresh every 2 minutes (120 seconds)
    }

    // Stop auto-refresh
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    // Load dashboard data from API
    async loadDashboardData() {
        try {
            const token = localStorage.getItem('auth_token');
            const headers = {
                'Content-Type': 'application/json'
            };
            
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            
            // Load statistics
            const statsResponse = await fetch(`${this.apiBaseUrl}dashboard.php?action=stats`, { headers });
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                if (statsData.success) {
                    this.data.stats = statsData.data;
                }
            }
            
            // Load campaigns
            const campaignsResponse = await fetch(`${this.apiBaseUrl}dashboard.php?action=campaigns`, { headers });
            if (campaignsResponse.ok) {
                const campaignsData = await campaignsResponse.json();
                if (campaignsData.success) {
                    this.data.campaigns = campaignsData.data;
                }
            }
            
            // Load events
            const eventsResponse = await fetch(`${this.apiBaseUrl}dashboard.php?action=events`, { headers });
            if (eventsResponse.ok) {
                const eventsData = await eventsResponse.json();
                if (eventsData.success) {
                    this.data.events = eventsData.data;
                }
            }
            
            // Load performance data
            const performanceResponse = await fetch(`${this.apiBaseUrl}dashboard.php?action=performance`, { headers });
            if (performanceResponse.ok) {
                const performanceData = await performanceResponse.json();
                if (performanceData.success) {
                    this.updatePerformanceData(performanceData.data);
                }
            }
            
            this.lastUpdate = new Date();
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            throw error;
        }
    }
    
    // Update performance data from API response
    updatePerformanceData(apiData) {
        // Update campaign performance
        if (apiData.campaignPerformance) {
            this.data.performanceData.campaignPerformance = apiData.campaignPerformance.map(c => parseFloat(c.engagement_rate));
        }
        
        // Update content views
        if (apiData.contentViews) {
            this.data.performanceData.contentViews = apiData.contentViews.map(v => parseInt(v.total_views) || 0);
        }
        
        // Update content types
        if (apiData.contentTypes) {
            this.data.performanceData.contentTypes = apiData.contentTypes.map(t => parseInt(t.count));
        }
        
        // Update audience engagement if available
        if (apiData.audienceEngagement) {
            this.data.performanceData.audienceEngagement = apiData.audienceEngagement.map(a => a.value);
        }
    }
    
    // Initialize fallback data if API is not available
    initializeFallbackData() {
        this.data = {
            campaigns: [
                { id: 1, name: 'Road Safety Awareness', status: 'active', progress: 75, engagement_rate: 68, start_date: '2023-10-01', end_date: '2023-12-15' },
                { id: 2, name: 'Cybersecurity for Seniors', status: 'planning', progress: 30, engagement_rate: 0, start_date: '2023-11-01', end_date: '2024-01-30' },
                { id: 3, name: 'Disaster Preparedness', status: 'draft', progress: 15, engagement_rate: 0, start_date: '2024-01-15', end_date: '2024-03-15' }
            ],
            events: [
                { id: 1, title: 'Road Safety Workshop', event_date: '2023-10-15 10:00:00', registered_count: 45, campaign_name: 'Road Safety Campaign' },
                { id: 2, title: 'Cyber Safety Seminar', event_date: '2023-10-22 14:00:00', registered_count: 32, campaign_name: 'Cyber Security Campaign' },
                { id: 3, title: 'Emergency Response Training', event_date: '2023-11-05 09:00:00', registered_count: 28, campaign_name: 'Disaster Preparedness' }
            ],
            stats: {
                activeCampaigns: 5,
                upcomingEvents: 12,
                contentItems: 47,
                surveyResponses: 1243
            },
            performanceData: {
                campaignPerformance: [68, 45, 52, 38],
                audienceEngagement: [25, 35, 20, 20],
                contentViews: [1200, 1900, 1500, 2100, 1800, 2500, 2200, 2800, 3200, 3800],
                contentTypes: [35, 25, 20, 15, 5]
            }
        };
    }
    
    // Refresh dashboard data from API
    async simulateDataUpdate() {
        try {
            await this.loadDashboardData();
        } catch (error) {
            console.error('Error refreshing dashboard data:', error);
            // Fallback to simulated updates if API fails
            this.data.stats.surveyResponses += Math.floor(Math.random() * 5);
            this.data.stats.contentItems += Math.floor(Math.random() * 2);
            this.lastUpdate = new Date();
        }
    }

    // Refresh the entire dashboard
    refreshDashboard() {
        // Only update data, not re-render entire UI to prevent blinking
        this.updateStats();
        this.updateCharts();
        
        // Show refresh feedback
        this.showRefreshFeedback();
    }

    // Update all charts with new data
    updateCharts() {
        Object.keys(this.charts).forEach(chartKey => {
            const chart = this.charts[chartKey];
            if (chart && this.data.performanceData[chartKey]) {
                chart.data.datasets[0].data = this.data.performanceData[chartKey];
                // Use 'none' animation to prevent blinking
                chart.update('none');
            }
        });
    }

    // Show refresh feedback
    showRefreshFeedback() {
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        }
        
        this.updateLastRefreshTime();
    }

    // Update last refresh time display
    updateLastRefreshTime() {
        const metaContainer = document.querySelector('.dashboard-meta');
        if (!metaContainer) return;

        let timeDisplay = metaContainer.querySelector('.last-update-time');
        if (!timeDisplay) {
            timeDisplay = document.createElement('small');
            timeDisplay.className = 'last-update-time';
            timeDisplay.style.cssText = 'color: var(--text-light);';
            metaContainer.appendChild(timeDisplay);
        }
        
        timeDisplay.textContent = `Last updated: ${this.lastUpdate.toLocaleTimeString()}`;
    }

    // Chart interaction handlers
    showCampaignDetails(index) {
        const campaigns = ['Road Safety', 'Cyber Security', 'Fire Prevention', 'Disaster Preparedness'];
        const campaignName = campaigns[index];
        const engagement = this.data.performanceData.campaignPerformance[index];
        
        alert(`Campaign: ${campaignName}\nEngagement Rate: ${engagement.toFixed(1)}%\n\nClick 'View Details' in the campaigns table for more information.`);
    }

    showAudienceSegmentDetails(index) {
        const segments = ['Active Participants', 'Occasional Engagers', 'Minimal Engagement', 'Not Engaged'];
        const segmentName = segments[index];
        const percentage = this.data.performanceData.audienceEngagement[index];
        
        alert(`Audience Segment: ${segmentName}\nPercentage: ${percentage}%\n\nUse the Audience Segmentation module for detailed analysis.`);
    }

    // Campaign detail functions
    viewCampaignDetails(campaignId) {
        const campaign = this.data.campaigns.find(c => c.id === campaignId);
        if (campaign) {
            alert(`Campaign Details:

Name: ${campaign.name}
Status: ${campaign.status}
Progress: ${campaign.progress}%
Engagement: ${campaign.engagement}%
Duration: ${campaign.startDate} to ${campaign.endDate}`);
        }
    }

    exportCampaignData(campaignId) {
        const campaign = this.data.campaigns.find(c => c.id === campaignId);
        if (campaign) {
            const data = `Campaign Export

Name: ${campaign.name}
Status: ${campaign.status}
Progress: ${campaign.progress}%
Engagement: ${campaign.engagement}%
Start Date: ${campaign.startDate}
End Date: ${campaign.endDate}`;
            
            // Create and download file
            const blob = new Blob([data], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${campaign.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_data.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }
    }

    viewEventDetails(eventId) {
        const event = this.data.events.find(e => e.id === eventId);
        if (event) {
            alert(`Event Details:

Title: ${event.title}
Date: ${event.date}
Registered: ${event.registered}
Campaign: ${event.campaign}`);
        }
    }

    // Export entire dashboard
    exportDashboard() {
        const exportData = {
            exportDate: new Date().toISOString(),
            statistics: this.data.stats,
            campaigns: this.data.campaigns,
            events: this.data.events,
            performanceData: this.data.performanceData
        };
        
        const jsonData = JSON.stringify(exportData, null, 2);
        const blob = new Blob([jsonData], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `dashboard_export_${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // Update chart time range
    updateChartTimeRange(chartIndex, timeRange) {
        // This would typically fetch new data based on the time range
        console.log(`Updating chart ${chartIndex} with time range: ${timeRange}`);
        
        // For demo purposes, just show a message
        const chartTitles = ['Campaign Performance', 'Audience Engagement', 'Content Performance', 'Content Types'];
        if (chartTitles[chartIndex]) {
            alert(`Time range updated to "${timeRange}" for ${chartTitles[chartIndex]} chart.\n\nIn a real application, this would fetch and display data for the selected time period.`);
        }
    }
    
    // Additional utility functions
    refreshCampaignTable() {
        this.renderCampaignTable();
        this.showSuccessMessage('Campaign table refreshed!');
    }
    
    exportContentData() {
        const contentData = {
            exportDate: new Date().toISOString(),
            contentViews: this.data.performanceData.contentViews,
            contentTypes: this.data.performanceData.contentTypes
        };
        
        const jsonData = JSON.stringify(contentData, null, 2);
        const blob = new Blob([jsonData], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `content_performance_${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    showSuccessMessage(message) {
        // Create success notification
        const successDiv = document.createElement('div');
        successDiv.className = 'success-notification';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        successDiv.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md);
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.parentNode.removeChild(successDiv);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize charts when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Make escapeHtml globally available
    window.escapeHtml = escapeHtml;
    
    // Initialize Dashboard Manager
    window.dashboard = new DashboardManager();
    
    // Initialize dashboard immediately if it's the active module
    const dashboardModule = document.getElementById('dashboard');
    if (dashboardModule && dashboardModule.classList.contains('active-module')) {
        // Wait for Chart.js to be ready
        setTimeout(() => {
            dashboard.init();
        }, 100);
    }
    
    // Initialize existing functionality
    if (typeof initializeEventCRUD === 'function') initializeEventCRUD();
    if (typeof initializeSurveyCRUD === 'function') initializeSurveyCRUD();
    
    // Initialize user profile and notifications
    window.userProfileManager = new UserProfileManager();
    
    // Initialize module navigation
    window.moduleManager = new ModuleManager();
    initializeModuleNavigation();
    // Campaign Performance Chart - handled by DashboardManager

    // Audience Engagement Chart - handled by DashboardManager

    // Content Performance Chart - handled by DashboardManager

    // Campaign Timeline Chart
    const timelineCtx = document.getElementById('campaignTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8'],
            datasets: [
                {
                    label: 'Road Safety',
                    data: [30, 45, 60, 65, 75, 80, 85, 90],
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'Cyber Security',
                    data: [10, 20, 30, 40, 50, 60, 70, 75],
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Completion (%)'
                    }
                }
            }
        }
    });

    // Content Type Chart - handled by DashboardManager

    // Event Attendance Chart
    const eventAttendanceCtx = document.getElementById('eventAttendanceChart')?.getContext('2d');
    if (eventAttendanceCtx) {
        new Chart(eventAttendanceCtx, {
            type: 'bar',
            data: {
                labels: ['Road Safety Workshop', 'Cyber Safety Seminar', 'Fire Prevention Demo', 'Emergency Training'],
                datasets: [{
                    label: 'Registered',
                    data: [45, 32, 28, 38],
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1
                }, {
                    label: 'Attended',
                    data: [38, 28, 25, 32],
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Survey Response Chart
    const surveyResponseCtx = document.getElementById('surveyResponseChart')?.getContext('2d');
    if (surveyResponseCtx) {
        new Chart(surveyResponseCtx, {
            type: 'pie',
            data: {
                labels: ['Very Satisfied', 'Satisfied', 'Neutral', 'Unsatisfied', 'Very Unsatisfied'],
                datasets: [{
                    data: [35, 40, 15, 7, 3],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(100, 116, 139, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Impact Over Time Chart
    const impactOverTimeCtx = document.getElementById('impactOverTimeChart')?.getContext('2d');
    if (impactOverTimeCtx) {
        new Chart(impactOverTimeCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Awareness Level',
                    data: [45, 48, 52, 55, 58, 62],
                    borderColor: 'rgb(37, 99, 235)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Demographic Chart
    const demographicCtx = document.getElementById('demographic-chart')?.getContext('2d');
    if (demographicCtx) {
        new Chart(demographicCtx, {
            type: 'bar',
            data: {
                labels: ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
                datasets: [{
                    label: 'Age Distribution',
                    data: [15, 25, 22, 18, 12, 8],
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Outreach Chart
    const outreachCtx = document.getElementById('outreach-chart')?.getContext('2d');
    if (outreachCtx) {
        new Chart(outreachCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Email Open Rate',
                    data: [22, 25, 27, 24, 26, 28, 30, 29, 31, 33],
                    borderColor: 'rgb(37, 99, 235)',
                    tension: 0.3
                }, {
                    label: 'SMS Response Rate',
                    data: [18, 20, 22, 21, 23, 25, 24, 26, 28, 30],
                    borderColor: 'rgb(16, 185, 129)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Add form submission handlers for new modules
    const campaignForm = document.querySelector('#campaignForm');
    const eventForm = document.querySelector('#eventForm');
    const surveyForm = document.querySelector('#surveyForm');
    const goalForm = document.querySelector('#goalForm');
    const messageForm = document.querySelector('#messageForm');
    const outreachForm = document.querySelector('#outreach-form');
    const smsAlertForm = document.querySelector('#sms-alert-form');

    const outreachTableBody = document.getElementById('outreach-table-body');
    const smsTableBody = document.getElementById('sms-table-body');
    const smsEmptyState = document.getElementById('sms-empty-state');
    const smsTemplateButtons = document.querySelectorAll('[data-sms-template]');

    function updateSmsEmptyState() {
        if (!smsEmptyState) return;
        const hasRows = !!(smsTableBody && smsTableBody.querySelector('tr'));
        smsEmptyState.style.display = hasRows ? 'none' : 'block';
    }

    if (smsTemplateButtons.length && smsAlertForm) {
        smsTemplateButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const title = btn.getAttribute('data-title') || '';
                const segment = btn.getAttribute('data-segment') || '';
                const message = btn.getAttribute('data-message') || '';
                const titleEl = smsAlertForm.querySelector('#sms-title');
                const segEl = smsAlertForm.querySelector('#sms-segment');
                const msgEl = smsAlertForm.querySelector('#sms-message');
                if (titleEl) titleEl.value = title;
                if (segEl) segEl.value = segment;
                if (msgEl) msgEl.value = message;
                // Prefill date/time to next hour as a convenience
                const dateEl = smsAlertForm.querySelector('#sms-schedule-date');
                const timeEl = smsAlertForm.querySelector('#sms-schedule-time');
                const now = new Date();
                now.setHours(now.getHours() + 1, 0, 0, 0);
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth()+1).padStart(2,'0');
                const dd = String(now.getDate()).padStart(2,'0');
                const hh = String(now.getHours()).padStart(2,'0');
                const min = String(now.getMinutes()).padStart(2,'0');
                if (dateEl) dateEl.value = `${yyyy}-${mm}-${dd}`;
                if (timeEl) timeEl.value = `${hh}:${min}`;
            });
        });
    }

    [campaignForm, eventForm, surveyForm, goalForm, messageForm, outreachForm, smsAlertForm].forEach(form => {
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formId = this.getAttribute('id') || 'form';
                switch (formId) {
                    case 'outreach-form':
                        // Gather values
                        const oName = this.querySelector('#outreach-name')?.value || 'Untitled';
                        const oSegmentEl = this.querySelector('#outreach-segment');
                        const oSegment = oSegmentEl ? (oSegmentEl.selectedOptions[0]?.text || oSegmentEl.value) : '';
                        // Build table row
                        if (outreachTableBody) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${escapeHtml(oName)}</td>
                                <td>${escapeHtml(oSegment)}</td>
                                <td>Just now</td>
                                <td>—</td>
                                <td>—</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>
                                    <button class="btn-icon blue" data-action="view" aria-label="View Campaign"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon blue" data-action="copy" aria-label="Duplicate Campaign"><i class="fas fa-copy"></i></button>
                                    <button class="btn-icon blue" data-action="edit" aria-label="Edit Campaign"><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon red" data-action="delete" aria-label="Delete Campaign"><i class="fas fa-trash"></i></button>
                                </td>
                            `;
                            outreachTableBody.prepend(tr);
                        }
                        alert('Outreach campaign scheduled/sent successfully!');
                        break;
                    case 'sms-alert-form':
                        // Gather values
                        const sTitle = this.querySelector('#sms-title')?.value || 'Untitled Alert';
                        const sSegmentEl = this.querySelector('#sms-segment');
                        const sSegment = sSegmentEl ? (sSegmentEl.selectedOptions[0]?.text || sSegmentEl.value) : '';
                        const sDate = this.querySelector('#sms-schedule-date')?.value || '';
                        const sTime = this.querySelector('#sms-schedule-time')?.value || '';
                        const when = sDate && sTime ? `${sDate} ${sTime}` : (sDate || sTime || 'Scheduled');
                        if (smsTableBody) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${escapeHtml(sTitle)}</td>
                                <td>${escapeHtml(sSegment)}</td>
                                <td>${escapeHtml(when)}</td>
                                <td><span class="status-badge planning">Planned</span></td>
                                <td>
                                    <button class="btn-icon blue" data-action="edit" aria-label="Edit Alert"><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon red" data-action="delete" aria-label="Delete Alert"><i class="fas fa-trash"></i></button>
                                </td>
                            `;
                            smsTableBody.prepend(tr);
                            updateSmsEmptyState();
                        }
                        alert('SMS alert scheduled successfully!');
                        break;
                    default:
                        alert('Form submitted successfully!');
                }
                this.reset();
            });
        }
    });

    // Module Navigation
    const moduleLinks = document.querySelectorAll('.module-link');
    const moduleSections = document.querySelectorAll('.module-section');
    
    moduleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            moduleLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Hide all module sections
            moduleSections.forEach(section => {
                section.classList.remove('active-module');
            });
            
            // Show the selected module
            const moduleId = this.getAttribute('data-module');
            const targetSection = document.getElementById(moduleId);
            if (targetSection) {
                targetSection.classList.add('active-module');
                
                // Initialize dashboard if dashboard module is selected
                if (moduleId === 'dashboard' && window.dashboard) {
                    setTimeout(() => {
                        if (!window.dashboard.isInitialized) {
                            window.dashboard.init();
                        }
                    }, 100); // Small delay to ensure DOM is ready
                }
            }
        });
    });

    // Sub-module Navigation
    // Supports different data attributes and section ID prefixes per module
    const setupSubNavigation = (navItemsSelector, sectionsSelector, dataAttr = 'data-section', activeClass = 'active', idPrefix = '') => {
        const navItems = document.querySelectorAll(navItemsSelector);
        const sections = document.querySelectorAll(sectionsSelector);
        
        if (navItems.length > 0 && sections.length > 0) {
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active class from all items
                    navItems.forEach(i => i.classList.remove(activeClass));
                    
                    // Add active class to clicked item
                    this.classList.add(activeClass);
                    
                    // Hide all sections
                    sections.forEach(section => {
                        section.classList.add('hidden');
                    });
                    
                    // Show the selected section
                    const sectionIdRaw = this.getAttribute(dataAttr);
                    let targetId = sectionIdRaw;
                    if (idPrefix && sectionIdRaw && !sectionIdRaw.startsWith(idPrefix)) {
                        targetId = idPrefix + sectionIdRaw;
                    }
                    console.log('[SubNav] click', { navItemsSelector, dataAttr, sectionIdRaw, targetId });
                    if (targetId) {
                        const targetSection = document.getElementById(targetId);
                        if (targetSection) {
                            targetSection.classList.remove('hidden');
                        }
                    }
                });
            });
        }
    };
    
    // Initialize sub-navigation for audience module
    setupSubNavigation('[data-section]', '#audience-section-profiling, #audience-section-segmentation, #audience-section-personal-outreach, #audience-section-sms-alerts, #audience-section-lists', 'data-section', 'active', 'audience-section-');
    
    // Initialize sub-navigation for content module
    setupSubNavigation('[data-content-section]', '#content-section-dashboard, #content-section-assets, #content-section-upload', 'data-content-section', 'active', 'content-section-');
    
    // Initialize sub-navigation for collaboration module
    setupSubNavigation('[data-collab-section]', '#collab-section-team, #collab-section-projects, #collab-section-messages, #collab-section-tasks', 'data-collab-section', 'active', 'collab-section-');

    // Fallback: if Audience SMS nav is active on load, ensure its section is shown
    const audienceSmsNav = document.querySelector('.content-sidebar [data-section="sms-alerts"]');
    const audienceSmsSection = document.getElementById('audience-section-sms-alerts');
    if (audienceSmsNav && audienceSmsSection && audienceSmsNav.classList.contains('active')) {
        const audienceSections = document.querySelectorAll('#audience-section-profiling, #audience-section-segmentation, #audience-section-personal-outreach, #audience-section-sms-alerts, #audience-section-lists');
        audienceSections.forEach(s => s.classList.add('hidden'));
        audienceSmsSection.classList.remove('hidden');
        console.log('[SubNav] fallback applied for SMS Alerts');
    }

    // Extra safety: delegated click fallback specifically for Audience sidebar
    const audienceSidebar = document.querySelector('#audience .content-sidebar');
    if (audienceSidebar) {
        audienceSidebar.addEventListener('click', (e) => {
            const item = e.target.closest('[data-section]');
            if (!item) return;
            const sectionKey = item.getAttribute('data-section');
            if (!sectionKey) return;
            const targetId = `audience-section-${sectionKey}`;
            const allAudienceSections = document.querySelectorAll('#audience-section-profiling, #audience-section-segmentation, #audience-section-personal-outreach, #audience-section-sms-alerts, #audience-section-lists');
            allAudienceSections.forEach(s => s.classList.add('hidden'));
            const toShow = document.getElementById(targetId);
            if (toShow) {
                toShow.classList.remove('hidden');
                setTimeout(() => toShow.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0);
                console.log('[SubNav] audience delegated fallback', { targetId });
            }
        });
    }

    // Personalization tag functionality
    const personalizationTags = document.querySelectorAll('.personalization-tag');
    const messageTextarea = document.querySelector('#outreach-form textarea');
    
    personalizationTags.forEach(tag => {
        tag.addEventListener('click', function() {
            const tagValue = this.getAttribute('data-tag');
            if (messageTextarea) {
                messageTextarea.value += tagValue + ' ';
            }
        });
    });

    // Schedule datetime toggle
    const scheduleRadios = document.querySelectorAll('input[name="schedule"]');
    const scheduleDatetime = document.getElementById('schedule-datetime');
    
    scheduleRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'later') {
                scheduleDatetime.style.display = 'block';
            } else {
                scheduleDatetime.style.display = 'none';
            }
        });
    });

    // Goal sliders
    const awarenessGoal = document.getElementById('awarenessGoal');
    const awarenessGoalValue = document.getElementById('awarenessGoalValue');
    const behaviorGoal = document.getElementById('behaviorGoal');
    const behaviorGoalValue = document.getElementById('behaviorGoalValue');
    
    if (awarenessGoal && awarenessGoalValue) {
        awarenessGoal.addEventListener('input', function() {
            awarenessGoalValue.textContent = this.value + '%';
        });
    }
    
    if (behaviorGoal && behaviorGoalValue) {
        behaviorGoal.addEventListener('input', function() {
            behaviorGoalValue.textContent = this.value + '%';
        });
    }

    // File upload area
    const fileUploadArea = document.getElementById('file-upload-area');
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');
    
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <i class="fas fa-file" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                    <div class="file-info">
                        <h4>${file.name}</h4>
                        <p class="file-size">${formatFileSize(file.size)}</p>
                    </div>
                    <div class="file-actions">
                        <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                fileList.appendChild(fileItem);
            }
        });
    }

    // Advanced AI Chatbot functionality
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotModal = document.getElementById('chatbotModal');
    const closeChatbot = document.getElementById('closeChatbot');
    const minimizeChatbot = document.getElementById('minimizeChatbot');
    const chatInput = document.getElementById('chatInput');
    const sendChatMessage = document.getElementById('sendChatMessage');
    const chatbotBody = document.getElementById('chatbotBody');
    const chatbotContainer = document.querySelector('.chatbot-container');
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    const typingIndicator = document.getElementById('typingIndicator');
    
    let isMinimized = false;
    let messageId = 1;
    
    // Knowledge base for the chatbot
    const knowledgeBase = {
        campaign: {
            keywords: ['campaign', 'planning', 'strategy', 'create', 'start', 'plan'],
            responses: [
                "Here are key steps for creating an effective safety campaign:\n\n1. **Define Clear Objectives** - Set specific, measurable goals\n2. **Identify Target Audience** - Understand who you're trying to reach\n3. **Develop Key Messages** - Create clear, actionable safety messages\n4. **Choose Distribution Channels** - Select the best ways to reach your audience\n5. **Create a Timeline** - Plan campaign phases and milestones\n6. **Measure Success** - Track engagement and behavior change\n\nWould you like me to elaborate on any of these steps?",
                "For a successful safety campaign, consider these best practices:\n\n• **Keep messages simple and actionable**\n• **Use local data and statistics**\n• **Include real stories and testimonials**\n• **Make it visually appealing**\n• **Provide clear next steps**\n• **Test messages with your audience first**\n\nWhat type of safety campaign are you planning?"
            ]
        },
        engagement: {
            keywords: ['engagement', 'audience', 'reach', 'interact', 'participate'],
            responses: [
                "To boost audience engagement in safety campaigns:\n\n📱 **Digital Strategies:**\n• Interactive social media content\n• Safety quizzes and polls\n• Video testimonials\n• Shareable infographics\n\n🏃 **Community Events:**\n• Safety workshops and demonstrations\n• Community challenges\n• Local partnerships\n• Peer-to-peer education\n\n📊 **Measurement:**\n• Track social media metrics\n• Monitor event attendance\n• Conduct follow-up surveys\n\nWhich engagement strategy interests you most?",
                "Effective audience engagement requires understanding your community:\n\n1. **Segment your audience** by demographics and interests\n2. **Use multiple touchpoints** - social media, events, partnerships\n3. **Make it personal** - use local examples and stories\n4. **Encourage participation** - not just passive consumption\n5. **Provide value** - practical tips they can use immediately\n\nWhat's your target audience for this campaign?"
            ]
        },
        impact: {
            keywords: ['impact', 'measure', 'metrics', 'success', 'results', 'analytics'],
            responses: [
                "Here are key metrics to measure campaign impact:\n\n📈 **Reach Metrics:**\n• Number of people exposed to content\n• Social media impressions and shares\n• Event attendance\n• Website traffic\n\n🎯 **Engagement Metrics:**\n• Click-through rates\n• Time spent with content\n• Survey responses\n• Active participation rates\n\n✅ **Outcome Metrics:**\n• Knowledge increase (pre/post surveys)\n• Behavior change indicators\n• Safety incident reduction\n• Community feedback\n\nWhich metrics are most important for your campaign?",
                "To effectively measure campaign impact:\n\n1. **Set baseline measurements** before launching\n2. **Track both leading and lagging indicators**\n3. **Use multiple data sources** for comprehensive view\n4. **Regular monitoring** throughout campaign\n5. **Post-campaign evaluation** for lessons learned\n\nRemember: Impact measurement should align with your original campaign objectives!"
            ]
        },
        content: {
            keywords: ['content', 'create', 'materials', 'design', 'video', 'poster'],
            responses: [
                "Content creation tips for safety campaigns:\n\n🎨 **Visual Content:**\n• Use high-contrast colors for visibility\n• Include clear, simple graphics\n• Ensure text is readable from distance\n• Use consistent branding\n\n📹 **Video Content:**\n• Keep videos under 2 minutes\n• Start with a hook in first 5 seconds\n• Include captions for accessibility\n• End with clear call-to-action\n\n📝 **Written Content:**\n• Use active voice and simple language\n• Include specific statistics when relevant\n• Provide actionable steps\n• Test readability level\n\nWhat type of content are you planning to create?"
            ]
        },
        safety: {
            keywords: ['safety', 'prevention', 'emergency', 'fire', 'cyber', 'road'],
            responses: [
                "I can provide information about various safety topics:\n\n🚗 **Road Safety:** Seat belts, speed limits, distracted driving\n🔥 **Fire Safety:** Smoke detectors, escape plans, prevention\n💻 **Cybersecurity:** Strong passwords, phishing awareness, data protection\n🏠 **Home Safety:** Accident prevention, emergency preparedness\n🏢 **Workplace Safety:** Training, protocols, risk assessment\n\nWhich safety topic would you like to discuss?"
            ]
        }
    };
    
    // Common responses for when specific topics aren't matched
    const commonResponses = [
        "That's a great question! While I specialize in safety campaign guidance, I'd be happy to help you think through this challenge. Could you provide more context about what you're trying to achieve?",
        "I'm here to help with your safety campaign needs! Could you tell me more about the specific aspect you'd like assistance with - campaign planning, content creation, audience engagement, or impact measurement?",
        "Let me help you with that! For the most relevant guidance, could you share more details about your safety campaign goals and target audience?"
    ];
    
    if (chatbotToggle && chatbotModal) {
        chatbotToggle.addEventListener('click', function() {
            chatbotModal.style.display = 'flex';
            chatInput.focus();
        });
        
        closeChatbot.addEventListener('click', function() {
            chatbotModal.style.display = 'none';
            isMinimized = false;
            chatbotContainer.classList.remove('minimized');
        });
        
        minimizeChatbot.addEventListener('click', function() {
            isMinimized = !isMinimized;
            chatbotContainer.classList.toggle('minimized', isMinimized);
        });
        
        // Click outside to close
        chatbotModal.addEventListener('click', function(e) {
            if (e.target === chatbotModal) {
                chatbotModal.style.display = 'none';
                isMinimized = false;
                chatbotContainer.classList.remove('minimized');
            }
        });
        
        // Quick action buttons
        quickActionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const question = this.getAttribute('data-question');
                if (question) {
                    addUserMessage(question);
                    processMessage(question);
                }
            });
        });
        
        function addUserMessage(text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message user-message';
            messageDiv.innerHTML = `
                <div class="message-avatar user-avatar">U</div>
                <div class="message-content">
                    <div class="message-text">${escapeHtml(text)}</div>
                    <div class="message-time">${getCurrentTime()}</div>
                </div>
            `;
            chatbotBody.appendChild(messageDiv);
            scrollToBottom();
        }
        
        function addBotMessage(text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message bot-message';
            messageDiv.innerHTML = `
                <div class="message-avatar bot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">${text}</div>
                    <div class="message-time">${getCurrentTime()}</div>
                </div>
            `;
            chatbotBody.appendChild(messageDiv);
            scrollToBottom();
        }
        
        function showTypingIndicator() {
            typingIndicator.style.display = 'flex';
            scrollToBottom();
        }
        
        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }
        
        function scrollToBottom() {
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        }
        
        function getCurrentTime() {
            return new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        function findBestMatch(userInput) {
            const input = userInput.toLowerCase();
            let bestMatch = null;
            let highestScore = 0;
            
            for (const [category, data] of Object.entries(knowledgeBase)) {
                const score = data.keywords.reduce((acc, keyword) => {
                    return acc + (input.includes(keyword) ? 1 : 0);
                }, 0);
                
                if (score > highestScore) {
                    highestScore = score;
                    bestMatch = data.responses;
                }
            }
            
            return bestMatch;
        }
        
        function processMessage(userInput) {
            showTypingIndicator();
            
            // Simulate processing time
            setTimeout(() => {
                hideTypingIndicator();
                
                const matchedResponses = findBestMatch(userInput);
                let response;
                
                if (matchedResponses && matchedResponses.length > 0) {
                    // Select a random response from matched category
                    response = matchedResponses[Math.floor(Math.random() * matchedResponses.length)];
                } else {
                    // Use a common response
                    response = commonResponses[Math.floor(Math.random() * commonResponses.length)];
                }
                
                addBotMessage(response);
            }, 1000 + Math.random() * 1000); // Random delay between 1-2 seconds
        }
        
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message === '') return;
            
            addUserMessage(message);
            processMessage(message);
            chatInput.value = '';
            sendChatMessage.disabled = true;
            
            setTimeout(() => {
                sendChatMessage.disabled = false;
            }, 2000);
        }
        
        if (sendChatMessage && chatInput) {
            sendChatMessage.addEventListener('click', sendMessage);
            
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Enable/disable send button based on input
            chatInput.addEventListener('input', function() {
                sendChatMessage.disabled = this.value.trim() === '';
            });
        }
    }

    // UserProfileManager class will handle profile and notification functionality
    
    // Profile modal functionality is handled by UserProfileManager class
    
    // Profile form submission
    // Profile form functionality is handled by UserProfileManager class
    
    // Notification system is now handled by UserProfileManager class

    // Cancel buttons
    const cancelButtons = document.querySelectorAll('[id$="Btn"]');
    cancelButtons.forEach(button => {
        if (button.id.includes('cancel')) {
            button.addEventListener('click', function() {
                const formId = this.id.replace('cancel', '').replace('Btn', '') + 'Form';
                const form = document.getElementById(formId);
                if (form) {
                    form.reset();
                }
            });
        }
    });

    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Responsive menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Check screen size and show/hide menu toggle
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                menuToggle.style.display = 'block';
            } else {
                menuToggle.style.display = 'none';
                sidebar.classList.remove('active');
            }
        }
        
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
    }
});



// Add these missing handlers
const personalOutreach = {
// Missing template preview
previewTemplate(templateId) {
const templates = {
    safety: "Safety Alert: Important update for {location}",
    cyber: "Cybersecurity Alert: New information for {organization}",
    emergency: "Emergency Update: Important notice for {area}"
};
return templates[templateId] || '';
},

// Missing message personalization
personalizeMessage(message, data) {
return message.replace(/{(\w+)}/g, (match, key) => data[key] || match);
},

// Missing validation
validateOutreachForm() {
const required = ['name', 'segment', 'message'];
let isValid = true;
required.forEach(field => {
    const element = document.querySelector(`#outreach-${field}`);
    if (!element || !element.value.trim()) {
        isValid = false;
        this.showError(element, 'This field is required');
    }
});
return isValid;
}
};


// Add these missing functions
const smsAlerts = {
// Missing character counter
setupCharCounter() {
const messageInput = document.querySelector('#sms-message');
const counter = document.createElement('div');
counter.className = 'char-count';
messageInput?.parentNode.appendChild(counter);

messageInput?.addEventListener('input', () => {
    const remaining = 160 - messageInput.value.length;
    counter.textContent = `${remaining} characters remaining`;
    counter.classList.toggle('error', remaining < 0);
});
},

// Missing priority handling
handlePriority(priority) {
const deliveryOptions = {
    high: { delay: '5 minutes', class: 'danger' },
    medium: { delay: '30 minutes', class: 'warning' },
    low: { delay: '2 hours', class: 'info' }
};
return deliveryOptions[priority];
}
};




// Add these form submission handlers
document.addEventListener('DOMContentLoaded', () => {
// Personal Outreach form handler
const outreachForm = document.querySelector('#outreach-form');
outreachForm?.addEventListener('submit', (e) => {
e.preventDefault();
const formData = new FormData(outreachForm);
const data = Object.fromEntries(formData);

if (personalOutreach.validateOutreachForm()) {
    // Process the form submission
    console.log('Submitting outreach:', data);
    alert('Outreach campaign created successfully!');
    outreachForm.reset();
}
});

// SMS Alert form handler
const smsForm = document.querySelector('#sms-alert-form');
smsForm?.addEventListener('submit', (e) => {
e.preventDefault();
const formData = new FormData(smsForm);
const data = Object.fromEntries(formData);

if (validateSMSForm(data)) {
    // Process the SMS alert
    console.log('Scheduling SMS:', data);
    alert('SMS Alert scheduled successfully!');
    smsForm.reset();
}
});
});



// Add these validation functions
function validateSMSForm(data) {
const errors = [];

if (!data.title?.trim()) {
errors.push('Title is required');
}

if (!data.message?.trim()) {
errors.push('Message is required');
} else if (data.message.length > 160) {
errors.push('Message cannot exceed 160 characters');
}

if (!data.segment) {
errors.push('Please select an audience segment');
}

if (errors.length > 0) {
alert(errors.join('\n'));
return false;
}

return true;
};


// Add error handling utilities
const errorHandling = {
showError(element, message) {
const errorDiv = document.createElement('div');
errorDiv.className = 'error-message';
errorDiv.textContent = message;
element.parentNode.appendChild(errorDiv);
element.classList.add('error');
},

clearError(element) {
const errorDiv = element.parentNode.querySelector('.error-message');
if (errorDiv) {
    errorDiv.remove();
}
element.classList.remove('error');
},

validateField(element, rules) {
this.clearError(element);
for (const rule of rules) {
    if (!rule.validate(element.value)) {
        this.showError(element, rule.message);
        return false;
    }
}
return true;
}
};




// Add template management functionality
const templateManager = {
templates: new Map(),

addTemplate(name, content) {
this.templates.set(name, content);
},

loadTemplate(name) {
return this.templates.get(name);
},

renderPreview(name, data) {
const template = this.loadTemplate(name);
return template ? personalOutreach.personalizeMessage(template, data) : '';
}
};


// Event and Survey CRUD Operations
let eventsData = [
    {id: 1, title: 'Road Safety Workshop', campaign: 'Road Safety Campaign', date: '2023-10-15', time: '10:00', location: 'City Community Center', description: 'Interactive workshop', maxAttendees: 50, registeredAttendees: 45, status: 'upcoming'},
    {id: 2, title: 'Cyber Safety Seminar', campaign: 'Cyber Security Campaign', date: '2023-10-22', time: '14:00', location: 'Central Library', description: 'Educational seminar', maxAttendees: 40, registeredAttendees: 32, status: 'upcoming'},
    {id: 3, title: 'Emergency Response Training', campaign: 'Disaster Preparedness', date: '2023-11-05', time: '09:00', location: 'Fire Station #3', description: 'Hands-on training', maxAttendees: 35, registeredAttendees: 28, status: 'upcoming'}
];

let surveysData = [
    {id: 1, title: 'Road Safety Awareness Survey', campaign: 'Road Safety Campaign', startDate: '2023-10-01', endDate: '2023-10-30', description: 'Assess community awareness', targetAudience: ['general', 'drivers'], responses: 245, status: 'active'},
    {id: 2, title: 'Cybersecurity Knowledge Assessment', campaign: 'Cyber Security Campaign', startDate: '2023-11-01', endDate: '2023-11-30', description: 'Evaluate cybersecurity knowledge', targetAudience: ['seniors'], responses: 0, status: 'draft'},
    {id: 3, title: 'Emergency Preparedness Survey', campaign: 'Disaster Preparedness', startDate: '2023-09-15', endDate: '2023-10-15', description: 'Measure emergency preparedness', targetAudience: ['general', 'parents'], responses: 189, status: 'completed'}
];

let eventIdCounter = 4, surveyIdCounter = 4;

function initializeEventCRUD() {
    setupEventNavigation();
    renderEventsList();
    setupEventForm();
}

function initializeSurveyCRUD() {
    setupSurveyNavigation();
    renderSurveysList();
    setupSurveyForm();
}

function setupEventNavigation() {
    document.querySelectorAll('[data-event-section]').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('[data-event-section]').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('[id^="event-section-"]').forEach(section => section.classList.add('hidden'));
            const targetSection = document.getElementById(`event-section-${this.getAttribute('data-event-section')}`);
            if (targetSection) targetSection.classList.remove('hidden');
        });
    });
}

function setupSurveyNavigation() {
    document.querySelectorAll('[data-feedback-section]').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('[data-feedback-section]').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('[id^="feedback-section-"]').forEach(section => section.classList.add('hidden'));
            const targetSection = document.getElementById(`feedback-section-${this.getAttribute('data-feedback-section')}`);
            if (targetSection) targetSection.classList.remove('hidden');
        });
    });
}

function renderEventsList() {
    const eventsList = document.querySelector('#event-section-upcoming .events-list');
    if (!eventsList) return;
    eventsList.innerHTML = '';
    eventsData.forEach(event => {
        const eventCard = document.createElement('div');
        eventCard.className = 'event-card';
        eventCard.innerHTML = `
            <div class="event-header">
                <h3 class="event-title">${event.title}</h3>
                <div class="event-date"><i class="fas fa-calendar"></i><span>${new Date(event.date + 'T' + event.time).toLocaleDateString()}</span></div>
            </div>
            <div class="event-details">
                <div class="event-detail"><i class="fas fa-map-marker-alt"></i><span>${event.location}</span></div>
                <div class="event-detail"><i class="fas fa-users"></i><span>${event.registeredAttendees} registered</span></div>
                <div class="event-detail"><i class="fas fa-tag"></i><span>${event.campaign}</span></div>
            </div>
            <div class="event-actions">
                <button class="btn-primary" onclick="manageAttendees(${event.id})">Manage</button>
                <button class="btn-secondary" onclick="sendReminders(${event.id})">Remind</button>
                <button class="btn-icon blue" onclick="editEvent(${event.id})"><i class="fas fa-edit"></i></button>
                <button class="btn-icon red" onclick="deleteEvent(${event.id})"><i class="fas fa-trash"></i></button>
            </div>
        `;
        eventsList.appendChild(eventCard);
    });
}

function renderSurveysList() {
    const surveysList = document.querySelector('#feedback-section-surveys .survey-list');
    if (!surveysList) return;
    surveysList.innerHTML = '';
    surveysData.forEach(survey => {
        const surveyItem = document.createElement('div');
        surveyItem.className = 'survey-item';
        const statusClass = survey.status === 'active' ? 'active' : survey.status === 'draft' ? 'planning' : 'completed';
        surveyItem.innerHTML = `
            <div class="survey-header">
                <h3 class="survey-title">${survey.title}</h3>
                <span class="status-badge ${statusClass}">${survey.status}</span>
            </div>
            <div class="survey-details">
                <div class="event-detail"><i class="fas fa-chart-bar"></i><span>${survey.responses} responses</span></div>
                <div class="event-detail"><i class="fas fa-calendar"></i><span>Ends: ${survey.endDate}</span></div>
                <div class="event-detail"><i class="fas fa-tag"></i><span>${survey.campaign}</span></div>
            </div>
            <div class="survey-actions">
                ${survey.status === 'draft' ? 
                    `<button class="btn-primary" onclick="previewSurvey(${survey.id})">Preview</button><button class="btn-secondary" onclick="publishSurvey(${survey.id})">Publish</button>` :
                    `<button class="btn-primary" onclick="viewSurveyResults(${survey.id})">Results</button><button class="btn-secondary" onclick="shareSurvey(${survey.id})">Share</button>`
                }
                <button class="btn-icon blue" onclick="editSurvey(${survey.id})"><i class="fas fa-edit"></i></button>
                <button class="btn-icon red" onclick="deleteSurvey(${survey.id})"><i class="fas fa-trash"></i></button>
            </div>
        `;
        surveysList.appendChild(surveyItem);
    });
}

function setupEventForm() {
    const eventForm = document.getElementById('eventForm');
    if (!eventForm) return;
    eventForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const eventData = {
            id: this.dataset.editingId ? parseInt(this.dataset.editingId) : eventIdCounter++,
            title: document.getElementById('eventName').value,
            campaign: document.getElementById('eventCampaign').value,
            date: document.getElementById('eventDate').value,
            time: document.getElementById('eventTime').value,
            location: document.getElementById('eventLocation').value,
            description: document.getElementById('eventDescription').value,
            maxAttendees: parseInt(document.getElementById('eventMaxAttendees').value) || 50,
            registeredAttendees: 0,
            status: 'upcoming'
        };
        if (this.dataset.editingId) {
            const index = eventsData.findIndex(e => e.id === eventData.id);
            if (index !== -1) eventsData[index] = eventData;
        } else {
            eventsData.push(eventData);
        }
        this.reset();
        delete this.dataset.editingId;
        renderEventsList();
        alert('Event saved successfully!');
        document.querySelector('[data-event-section="upcoming"]').click();
    });
}

function setupSurveyForm() {
    const surveyForm = document.getElementById('surveyForm');
    if (!surveyForm) return;
    surveyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const audienceSelect = document.getElementById('surveyAudience');
        const selectedAudience = Array.from(audienceSelect.selectedOptions).map(option => option.value);
        const surveyData = {
            id: this.dataset.editingId ? parseInt(this.dataset.editingId) : surveyIdCounter++,
            title: document.getElementById('surveyTitle').value,
            campaign: document.getElementById('surveyCampaign').value,
            startDate: document.getElementById('surveyStartDate').value,
            endDate: document.getElementById('surveyEndDate').value,
            description: document.getElementById('surveyDescription').value,
            targetAudience: selectedAudience,
            responses: 0,
            status: 'draft'
        };
        if (this.dataset.editingId) {
            const index = surveysData.findIndex(s => s.id === surveyData.id);
            if (index !== -1) surveysData[index] = surveyData;
        } else {
            surveysData.push(surveyData);
        }
        this.reset();
        delete this.dataset.editingId;
        renderSurveysList();
        alert('Survey saved successfully!');
        document.querySelector('[data-feedback-section="surveys"]').click();
    });
}

// CRUD Functions
function editEvent(id) {
    const event = eventsData.find(e => e.id === id);
    if (!event) return;
    document.getElementById('eventName').value = event.title;
    document.getElementById('eventCampaign').value = event.campaign;
    document.getElementById('eventDate').value = event.date;
    document.getElementById('eventTime').value = event.time;
    document.getElementById('eventLocation').value = event.location;
    document.getElementById('eventDescription').value = event.description;
    document.getElementById('eventMaxAttendees').value = event.maxAttendees;
    document.getElementById('eventForm').dataset.editingId = id;
    document.querySelector('[data-event-section="create"]').click();
}

function deleteEvent(id) {
    if (confirm('Delete this event?')) {
        const index = eventsData.findIndex(e => e.id === id);
        if (index !== -1) eventsData.splice(index, 1);
        renderEventsList();
        alert('Event deleted!');
    }
}

function editSurvey(id) {
    const survey = surveysData.find(s => s.id === id);
    if (!survey) return;
    document.getElementById('surveyTitle').value = survey.title;
    document.getElementById('surveyCampaign').value = survey.campaign;
    document.getElementById('surveyStartDate').value = survey.startDate;
    document.getElementById('surveyEndDate').value = survey.endDate;
    document.getElementById('surveyDescription').value = survey.description;
    const audienceSelect = document.getElementById('surveyAudience');
    Array.from(audienceSelect.options).forEach(option => {
        option.selected = survey.targetAudience.includes(option.value);
    });
    document.getElementById('surveyForm').dataset.editingId = id;
    document.querySelector('[data-feedback-section="create"]').click();
}

function deleteSurvey(id) {
    if (confirm('Delete this survey?')) {
        const index = surveysData.findIndex(s => s.id === id);
        if (index !== -1) surveysData.splice(index, 1);
        renderSurveysList();
        alert('Survey deleted!');
    }
}

function publishSurvey(id) {
    const survey = surveysData.find(s => s.id === id);
    if (survey && survey.status === 'draft') {
        survey.status = 'active';
        renderSurveysList();
        alert('Survey published!');
    }
}

function manageAttendees(id) { alert('Managing attendees for event ' + id); }
function sendReminders(id) { alert('Sending reminders for event ' + id); }
function previewSurvey(id) { alert('Previewing survey ' + id); }
function viewSurveyResults(id) { alert('Viewing results for survey ' + id); }
function shareSurvey(id) { alert('Sharing survey ' + id); }

// User Profile and Notification Management
class UserProfileManager {
    constructor() {
        this.notifications = [
            {
                id: 1,
                message: 'New event registration received',
                details: '3 new registrations for Road Safety Workshop',
                time: '2 minutes ago',
                unread: true,
                type: 'event'
            },
            {
                id: 2,
                message: 'Survey response threshold reached',
                details: 'Road Safety Awareness Survey has reached 200+ responses',
                time: '1 hour ago',
                unread: true,
                type: 'survey'
            },
            {
                id: 3,
                message: 'Campaign performance update',
                details: 'Cyber Security Campaign engagement increased by 15%',
                time: '3 hours ago',
                unread: false,
                type: 'campaign'
            },
            {
                id: 4,
                message: 'Content approval needed',
                details: '2 content items pending your review',
                time: '1 day ago',
                unread: false,
                type: 'content'
            }
        ];
        
        this.userProfile = {
            firstName: 'John',
            lastName: 'Doe',
            email: 'john.doe@safetycampaign.org',
            role: 'Campaign Manager',
            department: 'Public Safety',
            phone: '+1 (555) 123-4567',
            location: 'New York, NY',
            timezone: 'Eastern Time (ET)',
            language: 'English',
            avatar: 'JD'
        };
        
        this.initializeEventListeners();
        this.updateNotificationBadge();
        this.renderNotifications();
        this.populateProfileForm();
    }
    
    initializeEventListeners() {
        // Profile dropdown toggle
        const profileTrigger = document.getElementById('userProfileToggle');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (profileTrigger && profileDropdown) {
            profileTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
                // Close notification panel if open
                this.closeNotificationPanel();
            });
        }
        
        // Profile menu actions
        const profileMenuItems = document.querySelectorAll('.profile-menu-item');
        profileMenuItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const action = item.getAttribute('data-action');
                this.handleProfileAction(action);
                this.closeAllPanels();
            });
        });
        
        // Notification panel toggle
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationPanel = document.getElementById('notificationPanel');
        const notificationOverlay = document.getElementById('notificationOverlay');
        
        if (notificationIcon && notificationPanel) {
            notificationIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = notificationPanel.classList.contains('show');
                this.closeAllPanels();
                if (!isOpen) {
                    notificationPanel.classList.add('show');
                    if (notificationOverlay) notificationOverlay.classList.add('show');
                }
            });
        }
        
        // Close panels when clicking overlay
        if (notificationOverlay) {
            notificationOverlay.addEventListener('click', () => {
                this.closeAllPanels();
            });
        }
        
        // Profile modal
        const profileModal = document.getElementById('profileModal');
        const profileModalClose = document.getElementById('profileModalClose');
        
        if (profileModalClose) {
            profileModalClose.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event from bubbling up to the modal container
                console.log('Close button clicked'); // Debug log
                this.closeProfileModal();
            });
        }
        
        if (profileModal) {
            profileModal.addEventListener('click', (e) => {
                if (e.target === profileModal) {
                    console.log('Modal background clicked'); // Debug log
                    this.closeProfileModal();
                }
            });
        }
        
        // Profile form submission
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateProfile();
            });
        }
        
        // Cancel profile edit
        const cancelProfileEdit = document.getElementById('cancelProfileEdit');
        if (cancelProfileEdit) {
            cancelProfileEdit.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event from bubbling up
                console.log('Cancel button clicked'); // Debug log
                this.closeProfileModal();
            });
        }
        
        // Notification actions
        const markAllReadBtn = document.getElementById('markAllRead');
        
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllNotificationsRead();
            });
        }
        
        // Close panels when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#userProfileToggle') && !e.target.closest('#profileDropdown') && 
                !e.target.closest('#notificationIcon') && !e.target.closest('#notificationPanel')) {
                this.closeAllPanels();
            }
        });
        
        // Escape key to close panels
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllPanels();
                this.closeProfileModal();
            }
        });
    }
    
    
    handleProfileAction(action) {
        switch (action) {
            case 'view-profile':
                this.showProfileModal(false);
                break;
            case 'edit-profile':
                this.showProfileModal(true);
                break;
            case 'account-settings':
                this.showAccountSettings();
                break;
            case 'preferences':
                this.showPreferences();
                break;
            case 'help':
                this.showHelp();
                break;
            case 'logout':
                this.handleLogout();
                break;
            default:
                console.log('Unknown profile action:', action);
        }
    }
    
    showAccountSettings() {
        // Placeholder for account settings functionality
        alert('Account Settings functionality would be implemented here.');
    }
    
    showPreferences() {
        // Placeholder for preferences functionality
        alert('User Preferences functionality would be implemented here.');
    }
    
    showHelp() {
        // Placeholder for help functionality
        alert('Help & Support functionality would be implemented here.');
    }
    
    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            // In a real application, this would clear session data and redirect
            alert('Logout functionality would be implemented here.');
            // window.location.href = '/login';
        }
    }
    
    closeAllPanels() {
        const profileDropdown = document.getElementById('profileDropdown');
        const notificationPanel = document.getElementById('notificationPanel');
        const notificationOverlay = document.getElementById('notificationOverlay');
        
        if (profileDropdown) profileDropdown.classList.remove('show');
        if (notificationPanel) notificationPanel.classList.remove('show');
        if (notificationOverlay) notificationOverlay.classList.remove('show');
    }
    
    closeNotificationPanel() {
        const notificationPanel = document.getElementById('notificationPanel');
        const notificationOverlay = document.getElementById('notificationOverlay');
        
        if (notificationPanel) notificationPanel.classList.remove('show');
        if (notificationOverlay) notificationOverlay.classList.remove('show');
    }
    
    updateNotificationBadge() {
        const badge = document.getElementById('notificationBadge');
        const unreadCount = this.notifications.filter(n => n.unread).length;
        
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    renderNotifications() {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;
        
        if (this.notifications.length === 0) {
            notificationList.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--text-medium);">
                    <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }
        
        notificationList.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.unread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-details">${notification.details}</div>
                </div>
                <div class="notification-time">${notification.time}</div>
            </div>
        `).join('');
        
        // Add click handlers for individual notifications
        notificationList.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = parseInt(item.dataset.id);
                this.markNotificationRead(notificationId);
            });
        });
    }
    
    markNotificationRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && notification.unread) {
            notification.unread = false;
            this.updateNotificationBadge();
            this.renderNotifications();
        }
    }
    
    
    showSuccessMessage(message) {
        // Create and show a temporary success message
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        successDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md);
            z-index: 1001;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(successDiv);
        
        // Remove the message after 3 seconds
        setTimeout(() => {
            successDiv.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.parentNode.removeChild(successDiv);
                }
            }, 300);
        }, 3000);
    }
    
    markAllNotificationsRead() {
        this.notifications.forEach(notification => {
            notification.unread = false;
        });
        this.updateNotificationBadge();
        this.renderNotifications();
    }
    
    clearAllNotifications() {
        if (confirm('Are you sure you want to clear all notifications?')) {
            this.notifications = [];
            this.updateNotificationBadge();
            this.renderNotifications();
        }
    }
    
    addNotification(message, details, type = 'info') {
        const newNotification = {
            id: Date.now(),
            message,
            details,
            time: 'Just now',
            unread: true,
            type
        };
        
        this.notifications.unshift(newNotification);
        this.updateNotificationBadge();
        this.renderNotifications();
    }
    
    showProfileModal(editMode = false) {
        const profileModal = document.getElementById('profileModal');
        const modalTitle = document.getElementById('profileModalTitle');
        const formInputs = document.querySelectorAll('#profileForm input, #profileForm select');
        const saveBtn = document.getElementById('saveProfileBtn');
        const cancelBtn = document.getElementById('cancelProfileEdit');
        
        if (profileModal) {
            profileModal.classList.add('show');
            
            // Update modal title
            if (modalTitle) {
                modalTitle.textContent = editMode ? 'Edit Profile' : 'View Profile';
            }
            
            // Enable/disable form inputs based on mode
            formInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.disabled = !editMode;
                } else {
                    input.readOnly = !editMode;
                    if (editMode) {
                        input.classList.remove('read-only');
                    } else {
                        input.classList.add('read-only');
                    }
                }
            });
            
            // Show/hide form buttons
            if (saveBtn) saveBtn.style.display = editMode ? 'inline-flex' : 'none';
            if (cancelBtn) cancelBtn.textContent = editMode ? 'Cancel' : 'Close';
            
            // Populate form with current data
            this.populateProfileForm();
        }
    }
    
    closeProfileModal() {
        const profileModal = document.getElementById('profileModal');
        console.log('closeProfileModal called', profileModal); // Debug log
        if (profileModal) {
            profileModal.classList.remove('show');
            console.log('Modal closed'); // Debug log
        }
    }
    
    populateProfileForm() {
        const form = document.getElementById('profileForm');
        if (!form) return;
        
        // Populate form fields
        Object.keys(this.userProfile).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = this.userProfile[key];
            }
        });
        
        // Update avatar displays
        const navbarAvatar = document.getElementById('navbarUserAvatar');
        const dropdownAvatar = document.getElementById('dropdownUserAvatar');
        const profileAvatarLarge = document.getElementById('profileAvatarLarge');
        
        [navbarAvatar, dropdownAvatar, profileAvatarLarge].forEach(element => {
            if (element) {
                element.textContent = this.userProfile.avatar;
            }
        });
        
        // Update name displays
        const fullName = `${this.userProfile.firstName} ${this.userProfile.lastName}`;
        const navbarUserName = document.getElementById('navbarUserName');
        const userName = document.getElementById('userName');
        const profileFullName = document.getElementById('profileFullName');
        
        if (navbarUserName) navbarUserName.textContent = fullName;
        if (userName) userName.textContent = fullName;
        if (profileFullName) profileFullName.textContent = fullName;
        
        // Update role displays
        const userRole = document.getElementById('userRole');
        const profileRoleDisplay = document.getElementById('profileRoleDisplay');
        
        if (userRole) userRole.textContent = this.userProfile.role;
        if (profileRoleDisplay) profileRoleDisplay.textContent = this.userProfile.role;
        
        // Update email display
        const userEmail = document.getElementById('userEmail');
        if (userEmail) userEmail.textContent = this.userProfile.email;
    }
    
    updateProfile() {
        const form = document.getElementById('profileForm');
        if (!form) return;
        
        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        
        // Update user profile data
        Object.keys(this.userProfile).forEach(key => {
            if (formData.has(key)) {
                this.userProfile[key] = formData.get(key);
            }
        });
        
        // Update notification preferences
        const notificationPrefs = {
            emailNotifications: formData.has('emailNotifications'),
            pushNotifications: formData.has('pushNotifications'),
            smsNotifications: formData.has('smsNotifications')
        };
        
        this.userProfile.notificationPreferences = notificationPrefs;
        
        // Update avatar initials
        this.userProfile.avatar = `${this.userProfile.firstName.charAt(0)}${this.userProfile.lastName.charAt(0)}`;
        
        // Refresh all displays
        this.populateProfileForm();
        this.closeProfileModal();
        
        // Add success notification
        this.addNotification(
            'Profile updated successfully',
            'Your profile information has been saved',
            'success'
        );
        
        // Show success message
        this.showSuccessMessage('Profile updated successfully!');
    }
}



// Module Navigation System
class ModuleManager {
    constructor() {
        this.currentModule = 'dashboard';
    }
    
    switchToModule(moduleId) {
        // Remove active class from all module links
        document.querySelectorAll('.module-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to target module link
        const targetLink = document.querySelector(`[data-module="${moduleId}"]`);
        if (targetLink) {
            targetLink.classList.add('active');
        }
        
        // Hide all modules
        document.querySelectorAll('.module-section').forEach(section => {
            section.classList.remove('active-module');
        });
        
        // Show target module
        const targetModule = document.getElementById(moduleId);
        if (targetModule) {
            targetModule.classList.add('active-module');
            this.currentModule = moduleId;
            
            // Initialize dashboard if needed
            // Only initialize if not already initialized
            if (moduleId === 'dashboard' && window.dashboard && !window.dashboard.isInitialized) {
                setTimeout(() => {
                    dashboard.init();
                }, 100);
            }
        }
    }
}

function initializeModuleNavigation() {
    // Module links in sidebar
    const moduleLinks = document.querySelectorAll('.module-link');
    const moduleContainers = document.querySelectorAll('.module-section');
    
    moduleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const moduleId = this.getAttribute('data-module');
            
            // Remove active class from all links
            moduleLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Hide all modules
            moduleContainers.forEach(container => {
                container.classList.remove('active-module');
            });
            
            // Show selected module
            const selectedModule = document.getElementById(moduleId);
            if (selectedModule) {
                selectedModule.classList.add('active-module');
                
                // Initialize dashboard if dashboard module is selected
                // Only initialize if not already initialized
                if (moduleId === 'dashboard' && window.dashboard && !window.dashboard.isInitialized) {
                    setTimeout(() => {
                        dashboard.init();
                    }, 100);
                }
            }
        });
    });
    
    // Content module navigation
    const contentNavItems = document.querySelectorAll('.nav-item[data-content-section]');
    const contentSections = document.querySelectorAll('[id^="content-section-"]');
    
    contentNavItems.forEach(item => {
        item.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-content-section');
            
            // Remove active class from all nav items
            contentNavItems.forEach(nav => nav.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Hide all content sections
            contentSections.forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected section
            const selectedSection = document.getElementById(`content-section-${sectionId}`);
            if (selectedSection) {
                selectedSection.classList.remove('hidden');
            }
        });
    });
    
    // Category filtering for content
    const categoryItems = document.querySelectorAll('.category-item[data-content-category]');
    
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            const category = this.getAttribute('data-content-category');
            
            // Remove active class from all category items
            categoryItems.forEach(cat => cat.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Filter content based on category (placeholder functionality)
            console.log(`Filtering content by category: ${category}`);
        });
    });
}

// Utility function for escaping HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
