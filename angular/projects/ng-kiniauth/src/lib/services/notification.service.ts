import {Injectable} from '@angular/core';
import {KinibindRequestService} from 'ng-kinibind';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {BehaviorSubject} from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class NotificationService {

    public notificationCount = new BehaviorSubject<number>(0);

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {
    }

    public getNotification(id) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/notification/item', {
            params: {id}
        }).toPromise();
    }

    public getUserNotifications(projectKey = '', limit = '10', offset = '0') {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/notification/', {
            params: {projectKey, limit, offset}
        }).toPromise();
    }

    public getUnreadNotificationCount() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/notification/unreadCount')
            .toPromise().then(count => {
                this.notificationCount.next(count);
                return count;
            });
    }

    public markNotificationsRead(notificationIds: any[]) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/notification/markRead', notificationIds)
            .toPromise().then(() => {
                this.getUnreadNotificationCount();
            });
    }

    public markNotificationsUnread(notificationIds: any[]) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/notification/markUnread', notificationIds)
            .toPromise().then(() => {
                this.getUnreadNotificationCount();
            });
    }
}
