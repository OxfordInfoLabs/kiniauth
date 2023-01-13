import {Component, OnInit} from '@angular/core';
import {NotificationService} from '../../services/notification.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ka-notifications',
    templateUrl: './notifications.component.html',
    styleUrls: ['./notifications.component.sass']
})
export class NotificationsComponent implements OnInit {

    public notifications: any = [];
    public selectAll = false;
    public someSelected = false;

    constructor(private notificationService: NotificationService) {
    }

    ngOnInit() {
        this.notificationService.getUserNotifications()
            .then(notifications => this.notifications = notifications);
    }

    public selectAllChange(value) {
        this.notifications.forEach(notification => {
            notification._checked = value;
        });
        this.someSelected = _.some(this.notifications, '_checked');
    }

    public checkboxChange(value) {
        this.selectAll = _.every(this.notifications, '_checked');
        this.someSelected = _.some(this.notifications, '_checked');
    }

    public markSelectedRead() {
        const selected = _.filter(this.notifications, '_checked');
        this.notificationService.markNotificationsRead(_.map(selected, 'id')).then(() => {
            _.forEach(selected, notification => {
                notification.read = true;
            });
        });
    }

    public markSelectedUnread() {
        const selected = _.filter(this.notifications, '_checked');
        this.notificationService.markNotificationsUnread(_.map(selected, 'id')).then(() => {
            _.forEach(selected, notification => {
                notification.read = false;
            });
        });
    }
}
