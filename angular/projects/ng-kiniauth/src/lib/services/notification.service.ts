import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {BehaviorSubject} from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class NotificationService {

    public notificationCount = new BehaviorSubject<number>(0);

    constructor(private http: HttpClient,
                private config: KiniAuthModuleConfig) {
    }

    public getNotification(id) {
        return this.http.get(this.config.accessHttpURL + '/notification/item', {
            params: {id}
        }).toPromise();
    }

    public getUserNotifications(projectKey = '', limit = '10', offset = '0') {
        return this.http.get(this.config.accessHttpURL + '/notification/', {
            params: {projectKey, limit, offset}
        }).toPromise();
    }

    public getUnreadNotificationCount() {
        return this.http.get(this.config.accessHttpURL + '/notification/unreadCount')
            .toPromise().then((count: number) => {
                this.notificationCount.next(count);
                return count;
            });
    }

    public markNotificationsRead(notificationIds: any[]) {
        return this.http.post(this.config.accessHttpURL + '/notification/markRead', notificationIds)
            .toPromise().then(() => {
                this.getUnreadNotificationCount();
            });
    }

    public markNotificationsUnread(notificationIds: any[]) {
        return this.http.post(this.config.accessHttpURL + '/notification/markUnread', notificationIds)
            .toPromise().then(() => {
                this.getUnreadNotificationCount();
            });
    }
}
