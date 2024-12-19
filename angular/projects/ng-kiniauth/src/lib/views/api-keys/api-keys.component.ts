import {Component, OnInit} from '@angular/core';
import {Subscription} from 'rxjs/internal/Subscription';
import {AuthenticationService} from '../../services/authentication.service';
import {ApiKeysService} from '../../services/api-keys.service';
import {ProjectService} from '../../services/project.service';
import * as lodash from 'lodash';

const _ = lodash.default;

@Component({
    selector: 'ka-api-keys',
    templateUrl: './api-keys.component.html',
    styleUrls: ['./api-keys.component.sass']
})
export class ApiKeysComponent implements OnInit {

    public isAdmin = false;
    public apiKeys = [];
    public newDescription: string;
    public showNew = false;
    public loading = true;
    public assignableRoles: any;
    public roleAssignments: any = {};
    public _ = _;
    public activeProject: any;
    public canAccessAPIKeys = false;

    private projectSub: Subscription;

    constructor(private authService: AuthenticationService,
                public apiKeysService: ApiKeysService,
                private projectService: ProjectService) {
    }

    async ngOnInit(): Promise<any> {
        this.isAdmin = this.authService.isAdminNow();
        this.canAccessAPIKeys = this.projectService.doesActiveProjectHavePrivilege('feedaccess');

        if (this.canAccessAPIKeys) {
            this.projectSub = this.projectService.activeProject.subscribe(change => {
                this.loadApiKeys();
                this.activeProject = change;
                this.setupRoles();
            });

            this.loadApiKeys();
            this.setupRoles();
        }
    }

    public createNew() {
        this.showNew = true;
    }

    public async create() {
        const apiKeyId = await this.apiKeysService.create(this.newDescription);

        const roleAssignments = _.values(this.roleAssignments);
        await this.apiKeysService.updateAPIKeyScope(roleAssignments, apiKeyId);

        this.newDescription = '';
        this.showNew = false;
        this.loadApiKeys();
    }

    public updateCheckedRole(checked, value, roleAssignment) {
        if (checked) {
            roleAssignment.roleIds.push(value);
        } else {
            _.pull(roleAssignment.roleIds, value);
        }
    }

    public regenerateKeys(id) {
        const message = 'Are you sure you would like to regenerate these API Keys?';
        if (window.confirm(message)) {
            this.apiKeysService.regenerate(id)
                .then(this.loadApiKeys.bind(this));
        }
    }

    public suspend(id) {
        const message = 'Are you sure you would like to suspend these API Keys?';
        if (window.confirm(message)) {
            this.apiKeysService.suspend(id)
                .then(this.loadApiKeys.bind(this));
        }
    }

    public reactivate(id) {
        const message = 'Are you sure you would like to reactivate these API Keys?';
        if (window.confirm(message)) {
            this.apiKeysService.reactivate(id)
                .then(this.loadApiKeys.bind(this));
        }
    }

    public updateDescription(id, description) {
        this.apiKeysService.update(id, description)
            .then(this.loadApiKeys.bind(this));
    }

    public remove(id) {
        const message = 'Are you sure you would like to remove these API Keys?';
        if (window.confirm(message)) {
            this.apiKeysService.delete(id)
                .then(this.loadApiKeys.bind(this));
        }
    }

    public async setupRoles() {
        const assignableRoles = await this.apiKeysService.assignableRoles();
        this.assignableRoles = _.filter(assignableRoles, {scopeId: this.activeProject.projectKey});
        this.assignableRoles.forEach(role => {
            this.roleAssignments[role.scopeId] = {scope: role.scope, scopeId: role.scopeId, roleIds: []};
        });
    }

    public async loadApiKeys() {
        this.apiKeys = await this.apiKeysService.list();
        for (const apiKey of this.apiKeys) {
            const roles: any = await this.apiKeysService.getApiKeyRoles(apiKey.id);
            const projectRole = _.find(roles.Project, {scopeId: this.activeProject.projectKey});
            const roleStrings = projectRole ? _.map(_.filter(projectRole.roles), 'name') : '';
            apiKey.roleStrings = roleStrings.join(', ');
        }
        this.loading = false;
    }

}
