import { useEffect, useRef } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useAuth } from '../context/AuthContext';
import { API_BASE_URL } from '../utils/apiConfig';

export default function useNotifications() {
    const { user } = useAuth();
    const lastCheckedRef = useRef(new Date().toISOString());

    useEffect(() => {
        if (!user) return;

        const pollNotifications = async () => {
            try {
                const response = await axios.get(`${API_BASE_URL}/notifications_api.php?user_id=${user.id}&last_checked=${lastCheckedRef.current}`);
                const data = response.data;

                if (data.notifications && data.notifications.length > 0) {
                    // Update timestamp
                    lastCheckedRef.current = data.timestamp;

                    // Show notifications
                    data.notifications.forEach(notif => {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true
                        });

                        Toast.fire({
                            icon: 'info',
                            title: `訂單更新`,
                            text: `訂單 #${notif.order_number} 狀態已變更為: ${notif.status}`
                        });
                    });
                }
            } catch (error) {
                console.error("Poll error", error);
            }
        };

        // Poll every 30 seconds
        const intervalId = setInterval(pollNotifications, 30000);

        // Initial check ? Maybe skip to avoid span. Let's wait 30s.

        return () => clearInterval(intervalId);
    }, [user]);
}
