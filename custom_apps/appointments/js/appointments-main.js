/**
 * @copyright Copyright (c) 2023 NextCloud App Build
 *
 * @author NextCloud App Build
 *
 * @license AGPL-3.0-or-later
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the app
        const AppointmentsApp = {
            init: function() {
                // Get app container and data attributes
                this.container = document.getElementById('appointments-app');
                this.userId = this.container.dataset.userId;
                this.isTherapist = this.container.dataset.isTherapist === 'true';
                this.squareEnvironment = this.container.dataset.squareEnvironment;
                this.squareApplicationId = this.container.dataset.squareApplicationId;
                
                // Remove loading indicator
                document.getElementById('app-loading').remove();
                
                // Create app structure
                this.createAppStructure();
                
                // Initialize event listeners
                this.initEventListeners();
                
                // Load initial data
                this.loadData();
            },
            
            createAppStructure: function() {
                const appContainer = document.createElement('div');
                appContainer.className = 'appointments-container';
                
                // Create header
                const header = document.createElement('div');
                header.className = 'appointments-header';
                
                const title = document.createElement('h2');
                title.textContent = this.isTherapist ? 'Manage Your Appointments' : 'Your Therapy Appointments';
                
                const actions = document.createElement('div');
                actions.className = 'appointments-actions';
                
                if (this.isTherapist) {
                    // Add therapist-specific actions
                    const manageScheduleBtn = document.createElement('button');
                    manageScheduleBtn.className = 'primary';
                    manageScheduleBtn.textContent = 'Manage Schedule';
                    manageScheduleBtn.id = 'manage-schedule-btn';
                    actions.appendChild(manageScheduleBtn);
                } else {
                    // Add client-specific actions
                    const bookAppointmentBtn = document.createElement('button');
                    bookAppointmentBtn.className = 'primary';
                    bookAppointmentBtn.textContent = 'Book Appointment';
                    bookAppointmentBtn.id = 'book-appointment-btn';
                    actions.appendChild(bookAppointmentBtn);
                }
                
                header.appendChild(title);
                header.appendChild(actions);
                appContainer.appendChild(header);
                
                // Create tabs
                const tabs = document.createElement('div');
                tabs.className = 'tabs';
                
                const appointmentsTab = document.createElement('div');
                appointmentsTab.className = 'tab active';
                appointmentsTab.dataset.tab = 'appointments';
                appointmentsTab.textContent = 'Appointments';
                
                const invoicesTab = document.createElement('div');
                invoicesTab.className = 'tab';
                invoicesTab.dataset.tab = 'invoices';
                invoicesTab.textContent = 'Invoices';
                
                tabs.appendChild(appointmentsTab);
                tabs.appendChild(invoicesTab);
                
                if (this.isTherapist) {
                    const scheduleTab = document.createElement('div');
                    scheduleTab.className = 'tab';
                    scheduleTab.dataset.tab = 'schedule';
                    scheduleTab.textContent = 'Schedule';
                    tabs.appendChild(scheduleTab);
                }
                
                appContainer.appendChild(tabs);
                
                // Create content areas
                const contentAreas = document.createElement('div');
                contentAreas.className = 'tab-content';
                
                // Appointments content
                const appointmentsContent = document.createElement('div');
                appointmentsContent.className = 'tab-pane active';
                appointmentsContent.dataset.tab = 'appointments';
                appointmentsContent.id = 'appointments-list';
                appointmentsContent.innerHTML = '<div class="icon-loading"></div>';
                
                // Invoices content
                const invoicesContent = document.createElement('div');
                invoicesContent.className = 'tab-pane';
                invoicesContent.dataset.tab = 'invoices';
                invoicesContent.id = 'invoices-list';
                invoicesContent.innerHTML = '<div class="icon-loading"></div>';
                
                contentAreas.appendChild(appointmentsContent);
                contentAreas.appendChild(invoicesContent);
                
                // Schedule content (for therapists)
                if (this.isTherapist) {
                    const scheduleContent = document.createElement('div');
                    scheduleContent.className = 'tab-pane';
                    scheduleContent.dataset.tab = 'schedule';
                    scheduleContent.id = 'schedule-editor';
                    scheduleContent.innerHTML = '<div class="icon-loading"></div>';
                    contentAreas.appendChild(scheduleContent);
                }
                
                appContainer.appendChild(contentAreas);
                
                // Add to the DOM
                this.container.appendChild(appContainer);
            },
            
            initEventListeners: function() {
                // Tab switching
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.addEventListener('click', () => {
                        // Update active tab
                        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        
                        // Update active content
                        const tabName = tab.dataset.tab;
                        document.querySelectorAll('.tab-pane').forEach(pane => {
                            pane.classList.remove('active');
                            if (pane.dataset.tab === tabName) {
                                pane.classList.add('active');
                            }
                        });
                        
                        // Load data for the tab if needed
                        if (tabName === 'appointments') {
                            this.loadAppointments();
                        } else if (tabName === 'invoices') {
                            this.loadInvoices();
                        } else if (tabName === 'schedule' && this.isTherapist) {
                            this.loadSchedule();
                        }
                    });
                });
                
                // Button actions
                if (this.isTherapist) {
                    document.getElementById('manage-schedule-btn').addEventListener('click', () => {
                        // Switch to schedule tab
                        document.querySelector('.tab[data-tab="schedule"]').click();
                    });
                } else {
                    document.getElementById('book-appointment-btn').addEventListener('click', () => {
                        this.showBookAppointmentDialog();
                    });
                }
            },
            
            loadData: function() {
                // Load initial appointments data
                this.loadAppointments();
            },
            
            loadAppointments: function() {
                const appointmentsList = document.getElementById('appointments-list');
                appointmentsList.innerHTML = '<div class="icon-loading"></div>';
                
                // Fetch appointments from the API
                fetch(OC.generateUrl('/apps/appointments/appointments'))
                    .then(response => response.json())
                    .then(appointments => {
                        if (appointments.length === 0) {
                            appointmentsList.innerHTML = '<div class="empty-content"><div class="icon-calendar"></div><h2>No appointments found</h2></div>';
                            return;
                        }
                        
                        // Clear loading indicator
                        appointmentsList.innerHTML = '';
                        
                        // Create appointments list
                        const list = document.createElement('div');
                        list.className = 'appointments-list';
                        
                        appointments.forEach(appointment => {
                            const item = this.createAppointmentItem(appointment);
                            list.appendChild(item);
                        });
                        
                        appointmentsList.appendChild(list);
                    })
                    .catch(error => {
                        console.error('Error loading appointments:', error);
                        appointmentsList.innerHTML = '<div class="empty-content"><div class="icon-error"></div><h2>Error loading appointments</h2></div>';
                    });
            },
            
            createAppointmentItem: function(appointment) {
                const item = document.createElement('div');
                item.className = 'appointment-item';
                item.dataset.id = appointment.id;
                
                // Format dates
                const startDate = new Date(appointment.startTime);
                const endDate = new Date(appointment.endTime);
                const formattedDate = startDate.toLocaleDateString();
                const formattedStartTime = startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const formattedEndTime = endDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                // Create appointment header
                const header = document.createElement('div');
                header.className = 'appointment-header';
                
                const title = document.createElement('div');
                title.className = 'appointment-title';
                title.textContent = appointment.title;
                
                const time = document.createElement('div');
                time.className = 'appointment-time';
                time.textContent = `${formattedDate}, ${formattedStartTime} - ${formattedEndTime}`;
                
                header.appendChild(title);
                header.appendChild(time);
                
                // Create appointment details
                const details = document.createElement('div');
                details.className = 'appointment-details';
                
                const clientDetail = document.createElement('div');
                clientDetail.className = 'appointment-detail';
                clientDetail.innerHTML = `<strong>${this.isTherapist ? 'Client' : 'Therapist'}:</strong> ${this.isTherapist ? appointment.clientName : appointment.therapistName}`;
                
                const statusDetail = document.createElement('div');
                statusDetail.className = 'appointment-detail';
                statusDetail.innerHTML = `<strong>Status:</strong> ${appointment.status}`;
                
                details.appendChild(clientDetail);
                details.appendChild(statusDetail);
                
                if (appointment.notes) {
                    const notesDetail = document.createElement('div');
                    notesDetail.className = 'appointment-detail';
                    notesDetail.innerHTML = `<strong>Notes:</strong> ${appointment.notes}`;
                    details.appendChild(notesDetail);
                }
                
                // Create appointment actions
                const actions = document.createElement('div');
                actions.className = 'appointment-actions';
                
                if (this.isTherapist) {
                    // Therapist actions
                    const createInvoiceBtn = document.createElement('button');
                    createInvoiceBtn.textContent = 'Create Invoice';
                    createInvoiceBtn.addEventListener('click', () => {
                        this.createInvoice(appointment);
                    });
                    actions.appendChild(createInvoiceBtn);
                }
                
                // Common actions
                const cancelBtn = document.createElement('button');
                cancelBtn.textContent = 'Cancel';
                cancelBtn.classList.add('error');
                cancelBtn.addEventListener('click', () => {
                    this.cancelAppointment(appointment);
                });
                actions.appendChild(cancelBtn);
                
                // Assemble the item
                item.appendChild(header);
                item.appendChild(details);
                item.appendChild(actions);
                
                return item;
            },
            
            loadInvoices: function() {
                // Implementation for loading invoices
                const invoicesList = document.getElementById('invoices-list');
                invoicesList.innerHTML = '<div class="icon-loading"></div>';
                
                // TODO: Implement invoice loading
                invoicesList.innerHTML = '<div class="empty-content"><div class="icon-details"></div><h2>Invoice functionality coming soon</h2></div>';
            },
            
            loadSchedule: function() {
                // Implementation for loading therapist schedule
                const scheduleEditor = document.getElementById('schedule-editor');
                scheduleEditor.innerHTML = '<div class="icon-loading"></div>';
                
                // TODO: Implement schedule editor
                scheduleEditor.innerHTML = '<div class="empty-content"><div class="icon-calendar"></div><h2>Schedule editor coming soon</h2></div>';
            },
            
            showBookAppointmentDialog: function() {
                // Implementation for booking appointment dialog
                alert('Book appointment functionality coming soon');
            },
            
            createInvoice: function(appointment) {
                // Implementation for creating an invoice
                alert('Create invoice functionality coming soon');
            },
            
            cancelAppointment: function(appointment) {
                // Implementation for canceling an appointment
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    // TODO: Implement appointment cancellation
                    alert('Appointment cancellation coming soon');
                }
            }
        };
        
        // Initialize the app
        AppointmentsApp.init();
    });
})();