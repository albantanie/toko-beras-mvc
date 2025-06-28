import Swal from 'sweetalert2';

// SweetAlert2 configuration and utilities
export const SweetAlert = {
    // Success alerts
    success: {
        create: (itemName: string = 'item') => {
            return Swal.fire({
                icon: 'success',
                title: 'Created Successfully!',
                text: `${itemName} has been created successfully.`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: '#f0f9ff',
                color: '#0c4a6e',
                iconColor: '#059669',
            });
        },
        
        update: (itemName: string = 'item') => {
            return Swal.fire({
                icon: 'success',
                title: 'Updated Successfully!',
                text: `${itemName} has been updated successfully.`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: '#f0f9ff',
                color: '#0c4a6e',
                iconColor: '#059669',
            });
        },
        
        delete: (itemName: string = 'item') => {
            return Swal.fire({
                icon: 'success',
                title: 'Deleted Successfully!',
                text: `${itemName} has been deleted successfully.`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: '#fef2f2',
                color: '#7f1d1d',
                iconColor: '#dc2626',
            });
        },
        
        custom: (title: string, text: string) => {
            return Swal.fire({
                icon: 'success',
                title,
                text,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: '#f0f9ff',
                color: '#0c4a6e',
                iconColor: '#059669',
            });
        }
    },

    // Error alerts
    error: {
        create: (itemName: string = 'item', error?: string) => {
            return Swal.fire({
                icon: 'error',
                title: 'Creation Failed!',
                text: error || `Failed to create ${itemName}. Please try again.`,
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#dc2626',
                background: '#fef2f2',
                color: '#7f1d1d',
            });
        },
        
        update: (itemName: string = 'item', error?: string) => {
            return Swal.fire({
                icon: 'error',
                title: 'Update Failed!',
                text: error || `Failed to update ${itemName}. Please try again.`,
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#dc2626',
                background: '#fef2f2',
                color: '#7f1d1d',
            });
        },
        
        delete: (itemName: string = 'item', error?: string) => {
            return Swal.fire({
                icon: 'error',
                title: 'Deletion Failed!',
                text: error || `Failed to delete ${itemName}. Please try again.`,
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#dc2626',
                background: '#fef2f2',
                color: '#7f1d1d',
            });
        },
        
        validation: (errors: Record<string, string[]>) => {
            const errorMessages = Object.values(errors).flat().join('\n');
            return Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: errorMessages,
                confirmButtonText: 'Fix Errors',
                confirmButtonColor: '#dc2626',
                background: '#fef2f2',
                color: '#7f1d1d',
            });
        },
        
        custom: (title: string, text: string) => {
            return Swal.fire({
                icon: 'error',
                title,
                text,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc2626',
                background: '#fef2f2',
                color: '#7f1d1d',
            });
        }
    },

    // Confirmation dialogs
    confirm: {
        delete: (itemName: string = 'item') => {
            return Swal.fire({
                icon: 'warning',
                title: 'Are you sure?',
                text: `You are about to delete this ${itemName}. This action cannot be undone!`,
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                focusCancel: true,
                background: '#fffbeb',
                color: '#92400e',
            });
        },
        
        update: (itemName: string = 'item') => {
            return Swal.fire({
                icon: 'question',
                title: 'Confirm Update',
                text: `Are you sure you want to update this ${itemName}?`,
                showCancelButton: true,
                confirmButtonText: 'Yes, Update!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                background: '#f0f9ff',
                color: '#0c4a6e',
            });
        },
        
        custom: (title: string, text: string, confirmText: string = 'Confirm') => {
            return Swal.fire({
                icon: 'question',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                background: '#f0f9ff',
                color: '#0c4a6e',
            });
        }
    },

    // Loading states
    loading: {
        show: (title: string = 'Processing...', text: string = 'Please wait while we process your request.') => {
            return Swal.fire({
                title,
                text,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        
        hide: () => {
            Swal.close();
        }
    },

    // Info alerts
    info: {
        stockAlert: (productName: string, currentStock: number, minStock: number) => {
            return Swal.fire({
                icon: 'warning',
                title: 'Low Stock Alert!',
                html: `
                    <div class="text-left">
                        <p><strong>Product:</strong> ${productName}</p>
                        <p><strong>Current Stock:</strong> ${currentStock}</p>
                        <p><strong>Minimum Stock:</strong> ${minStock}</p>
                        <p class="text-red-600 mt-2">Please restock this item soon!</p>
                    </div>
                `,
                confirmButtonText: 'Understood',
                confirmButtonColor: '#f59e0b',
                background: '#fffbeb',
                color: '#92400e',
            });
        },
        
        paymentConfirmed: (orderNumber: string, amount: number) => {
            return Swal.fire({
                icon: 'success',
                title: 'Payment Confirmed!',
                html: `
                    <div class="text-left">
                        <p><strong>Order:</strong> #${orderNumber}</p>
                        <p><strong>Amount:</strong> Rp ${amount.toLocaleString('id-ID')}</p>
                        <p class="text-green-600 mt-2">Order is now ready for preparation!</p>
                    </div>
                `,
                confirmButtonText: 'Continue',
                confirmButtonColor: '#059669',
                background: '#f0f9ff',
                color: '#0c4a6e',
            });
        },
        
        orderReady: (orderNumber: string, pickupMethod: string) => {
            return Swal.fire({
                icon: 'info',
                title: 'Order Ready for Pickup!',
                html: `
                    <div class="text-left">
                        <p><strong>Order:</strong> #${orderNumber}</p>
                        <p><strong>Pickup Method:</strong> ${pickupMethod}</p>
                        <p class="text-blue-600 mt-2">Customer has been notified!</p>
                    </div>
                `,
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6',
                background: '#f0f9ff',
                color: '#0c4a6e',
            });
        }
    }
};

// Specific alerts for rice store operations
export const RiceStoreAlerts = {
    product: {
        created: (productName: string) => SweetAlert.success.create(`Product "${productName}"`),
        updated: (productName: string) => SweetAlert.success.update(`Product "${productName}"`),
        deleted: (productName: string) => SweetAlert.success.delete(`Product "${productName}"`),
        confirmDelete: (productName: string) => SweetAlert.confirm.delete(`product "${productName}"`),
    },
    
    transaction: {
        created: (orderNumber: string) => SweetAlert.success.custom('Transaction Created!', `Order #${orderNumber} has been created successfully.`),
        updated: (orderNumber: string) => SweetAlert.success.custom('Transaction Updated!', `Order #${orderNumber} has been updated successfully.`),
        deleted: (orderNumber: string) => SweetAlert.success.delete(`Transaction #${orderNumber}`),
        confirmDelete: (orderNumber: string) => SweetAlert.confirm.delete(`transaction #${orderNumber}`),
    },
    
    user: {
        created: (userName: string) => SweetAlert.success.create(`User "${userName}"`),
        updated: (userName: string) => SweetAlert.success.update(`User "${userName}"`),
        deleted: (userName: string) => SweetAlert.success.delete(`User "${userName}"`),
        confirmDelete: (userName: string) => SweetAlert.confirm.delete(`user "${userName}"`),
    },
    
    stock: {
        updated: (productName: string) => SweetAlert.success.custom('Stock Updated!', `Stock for "${productName}" has been updated successfully.`),
        lowStock: (productName: string, currentStock: number, minStock: number) => 
            SweetAlert.info.stockAlert(productName, currentStock, minStock),
    },
    
    order: {
        paymentConfirmed: (orderNumber: string, amount: number) => 
            SweetAlert.info.paymentConfirmed(orderNumber, amount),
        readyForPickup: (orderNumber: string, pickupMethod: string) => 
            SweetAlert.info.orderReady(orderNumber, pickupMethod),
        completed: (orderNumber: string) => 
            SweetAlert.success.custom('Order Completed!', `Order #${orderNumber} has been completed successfully.`),
        paymentRejected: (orderNumber: string) => 
            SweetAlert.success.custom('Payment Rejected!', `Payment proof for order #${orderNumber} has been rejected. Customer will be notified to upload new proof.`),
    }
};

export default SweetAlert;
