import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {NotificationService} from '../../../services/notification.service';

@Component({
    selector: 'ka-notification',
    templateUrl: './notification.component.html',
    styleUrls: ['./notification.component.sass']
})
export class NotificationComponent implements OnInit {

    public notification: any;

    private notificationId;

    constructor(private route: ActivatedRoute,
                private notificationService: NotificationService) {
    }

    ngOnInit() {
        this.notificationId = this.route.snapshot.params.id;
        this.notificationService.getNotification(this.notificationId).then((notification: any) => {
            if (notification && !notification.read) {
                notification.read = true;
                this.notificationService.markNotificationsRead([this.notificationId]);
            }
            this.notification = notification;
        });
    }

    public updateNotificationRead(event) {
        const markFunction = event ? 'markNotificationsRead' : 'markNotificationsUnread';
        this.notificationService[markFunction]([this.notificationId]);
    }

}
